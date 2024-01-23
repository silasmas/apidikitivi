<?php

namespace App\Http\Controllers\API;

use App\Models\Book;
use Illuminate\Http\Request;
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
            'author_names' => $request->author_names,
            'price' => $request->price,
            'for_youth' => $request->for_youth,
            'type_id' => $request->type_id
        ];
        // Select all books of an author to check unique constraint
        $books = Book::where('author_names', $inputs['author_names'])->get();

        // Validate required fields
        if (trim($inputs['book_title']) == null) {
            return $this->handleError($inputs['book_title'], __('validation.required'), 400);
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
}
