<?php

namespace App\Http\Controllers\API;

use App\Models\Book;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Book as ResourcesBook;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class BookController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $books = Book::orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesBook::collection($books), __('notifications.find_all_books_success'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Get inputs
        $inputs = [
            'book_title' => $request->book_title,
            'book_url' => $request->book_url,
            'author' => $request->author,
            'editor' => $request->editor,
            'cover_url' => $request->file('cover_url'),
            'price' => $request->price,
            'for_youth' => $request->for_youth,
            'type_id' => $request->type_id
        ];
        // Select all books of an author to check unique constraint
        $books = Book::where('author_names', $inputs['author_names'])->get();

        // Validate required fields
        if ($inputs['type_id'] == null) {
            return $this->handleError(__('validation.custom.type.required'), __('validation.required'), 400);
        }

        if (trim($inputs['book_title']) == null) {
            return $this->handleError(__('validation.custom.title.required'), __('validation.required'), 400);
        }

        if (trim($inputs['book_url']) == null) {
            return $this->handleError($inputs['book_url'], __('validation.required'), 400);
        }

        // Check if book title already exists
        foreach ($books as $another_book):
            if ($another_book->book_title == $inputs['book_title']) {
                return $this->handleError($inputs['book_title'], __('validation.custom.title.exists'), 400);
            }
        endforeach;

        $book = Book::create($inputs);

		if ($request->file('cover_url') != null) {
			$cover_url = 'images/books/' . $book->id . '/cover.' . $request->file('cover_url')->extension();

			// Upload cover
			Storage::url(Storage::disk('public')->put($cover_url, $inputs['cover_url']));

            $book->update([
                'cover_url' => $cover_url,
                'updated_at' => now()
            ]);
        }

        return $this->handleResponse(new ResourcesBook($book), __('notifications.create_book_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $book = Book::find($id);

        if (is_null($book)) {
            return $this->handleError(__('notifications.find_book_404'));
        }

        return $this->handleResponse(new ResourcesBook($book), __('notifications.find_book_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Book  $book
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Book $book)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'book_title' => $request->book_title,
            'book_url' => $request->book_url,
            'author_names' => $request->author_names,
            'price' => $request->price,
            'for_youth' => $request->for_youth,
            'type_id' => $request->type_id
        ];
        // Select all books of an author to check unique constraint
        $books = Book::where('author_names', $inputs['author_names'])->get();
        $current_book = Book::find($inputs['id']);

        if ($inputs['book_title'] != null) {
            foreach ($books as $another_book):
                if ($current_book->book_title != $inputs['book_title']) {
                    if ($another_book->book_title == $inputs['book_title']) {
                        return $this->handleError($inputs['book_title'], __('validation.custom.title.exists'), 400);
                    }
                }
            endforeach;

            $book->update([
                'book_title' => $request->book_title,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['book_url'] != null) {
            $book->update([
                'book_url' => $request->book_url,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['author_names'] != null) {
            $book->update([
                'author_names' => $request->author_names,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['price'] != null) {
            $book->update([
                'price' => $request->price,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['for_youth'] != null) {
            $book->update([
                'for_youth' => $request->for_youth,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['type_id'] != null) {
            $book->update([
                'type_id' => $request->type_id,
                'updated_at' => now(),
            ]);
        }

        return $this->handleResponse(new ResourcesBook($book), __('notifications.update_book_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Book  $book
     * @return \Illuminate\Http\Response
     */
    public function destroy(Book $book)
    {
        $book->delete();

        $books = Book::all();

        return $this->handleResponse(ResourcesBook::collection($books), __('notifications.delete_book_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Get all by title.
     *
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function search($data)
    {
        $books = Book::where('book_title', 'LIKE', '%' . $data . '%')->get();

        return $this->handleResponse(ResourcesBook::collection($books), __('notifications.find_all_books_success'));
    }

    /**
     * Get all by age.
     *
     * @param  int $for_youth
     * @return \Illuminate\Http\Response
     */
    public function findAllByAge($for_youth)
    {
        $books = Book::where('for_youth', $for_youth)->get();

        return $this->handleResponse(ResourcesBook::collection($books), __('notifications.find_all_books_success'));
    }

    /**
     * Get all by age and type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $for_youth
     * @param  int $type_id
     * @return \Illuminate\Http\Response
     */
    public function findAllByAgeType($for_youth, $type_id)
    {
        $books = Book::where([['for_youth', $for_youth], ['type_id', $type_id]])->get();

        return $this->handleResponse(ResourcesBook::collection($books), __('notifications.find_all_books_success'));
    }

    /**
     * Add book cover in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function addImage(Request $request, $id)
    {
        $inputs = [
            'book_id' => $request->entity_id,
            'image_64' => $request->base64image
        ];

        // $extension = explode('/', explode(':', substr($inputs['image_64'], 0, strpos($inputs['image_64'], ';')))[1])[1];
        $replace = substr($inputs['image_64'], 0, strpos($inputs['image_64'], ',') + 1);
        // Find substring from replace here eg: data:image/png;base64,
        $image = str_replace($replace, '', $inputs['image_64']);
        $image = str_replace(' ', '+', $image);

        // Clean selected "books" directory
        $file = new Filesystem;
        $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/books/' . $inputs['book_id']);
        // Create image URL
        $image_url = 'images/books/' . $inputs['book_id'] . '/' . Str::random(50) . '.png';

        // Upload image
        Storage::url(Storage::disk('public')->put($image_url, base64_decode($image)));

		$book = Book::find($id);

        $book->update([
            'cover_url' => $image_url,
            'updated_at' => now()
        ]);

        return $this->handleResponse(new ResourcesBook($book), __('notifications.update_book_success'));
	}
}
