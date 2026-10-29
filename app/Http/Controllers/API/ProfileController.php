<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\Profile as ResourcesProfile;
use App\Http\Resources\User as ResourcesUser;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Nette\Utils\Random;

/**
 * Family profiles (Bloc 3) : the parent keeps his account and manages the children profiles.
 * - A child opens his profile by tapping his avatar, without any code.
 * - The parental code only protects the settings (add / edit / delete a child, preferences)
 *   and the way back from a child profile to the parent account.
 */
class ProfileController extends BaseController
{
    /**
     * Themes a parent can check to guide recommendations (slide 8).
     */
    public const PREFERENCE_THEMES = ['bible_stories', 'jesus', 'faith_character', 'prayer', 'worship_music', 'films', 'teachings'];

    /**
     * List the family profiles ("Qui regarde ?").
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $current = $request->user();
        $owner = $current->familyOwner();
        $children = User::where('belongs_to', $owner->id)->orderBy('id')->get();

        return $this->handleResponse([
            'current_id' => $current->id,
            'has_parental_code' => !empty($owner->parental_code),
            'owner' => new ResourcesProfile($owner),
            'children' => ResourcesProfile::collection($children),
        ], __('notifications.find_profiles_success'));
    }

    /**
     * Open a profile of the family and get its session.
     * Child profiles need no code ; going back to the parent account from a child needs the parental code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function switchProfile(Request $request, $id)
    {
        $current = $request->user();
        $owner = $current->familyOwner();
        $target = User::find($id);

        if (is_null($target)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        if (!$this->isInFamily($owner, $target)) {
            return $this->handleError(__('notifications.profile_not_in_family'), [], 403);
        }

        $leaving_child_profile = $target->id == $owner->id && $current->id != $owner->id;

        if ($leaving_child_profile && !empty($owner->parental_code) && !$this->codeMatches($owner, $request->parental_code)) {
            return $this->handleError(__('notifications.parental_code_invalid'), [], 403);
        }

        $token = $target->createToken('auth_token')->plainTextToken;

        $target->update([
            'api_token' => $token,
            'updated_at' => now(),
        ]);

        return $this->handleResponse(new ResourcesUser($target), __('notifications.switch_profile_success'));
    }

    /**
     * Check the parental code (to unlock the parental settings in the app).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyParentalCode(Request $request)
    {
        $owner = $request->user()->familyOwner();

        if (empty($owner->parental_code)) {
            return $this->handleError(__('notifications.parental_code_required'), [], 422);
        }

        if (!$this->codeMatches($owner, $request->parental_code)) {
            return $this->handleError(__('notifications.parental_code_invalid'), [], 403);
        }

        return $this->handleResponse(['valid' => true], __('notifications.parental_code_valid'));
    }

    /**
     * Create or change the parental code.
     * Changing it needs the current code ; creating the first one needs the account password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateParentalCode(Request $request)
    {
        $current = $request->user();

        if ($current->belongs_to != null) {
            return $this->handleError(__('notifications.only_parent_allowed'), [], 403);
        }

        if (!preg_match('/^[0-9A-Za-z]{4,8}$/', (string) $request->new_code)) {
            return $this->handleError($request->new_code, __('notifications.parental_code_format'), 400);
        }

        if (!empty($current->parental_code)) {
            if (!$this->codeMatches($current, $request->current_code)) {
                return $this->handleError(__('notifications.parental_code_invalid'), [], 403);
            }

        } else {
            if (empty($request->password) or !Hash::check($request->password, $current->password)) {
                return $this->handleError(__('auth.password'), [], 403);
            }
        }

        $current->update([
            'parental_code' => $request->new_code,
            'updated_at' => now(),
        ]);

        return $this->handleResponse(['has_parental_code' => true], __('notifications.parental_code_updated'));
    }

    /**
     * Add a child profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $owner = $request->user();

        if ($error = $this->checkParentAccess($request, $owner)) {
            return $error;
        }

        $validator = Validator::make($request->all(), $this->childRules(true));

        if ($validator->fails()) {
            return $this->handleError($validator->errors(), __('notifications.child_invalid'), 400);
        }

        $status_intermediate = Status::where('status_name->fr', 'Intermédiaire')->first();

        $child = User::create([
            'firstname' => trim($request->firstname),
            'lastname' => $request->filled('lastname') ? trim($request->lastname) : $owner->lastname,
            'surname' => $request->surname,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            // Même convention que UserController@store pour un enfant
            'email' => 'child-' . Random::generate(10, '0-9a-zA-Z') . '@no_mail.com',
            'username' => $this->uniqueUsername($request->firstname, $owner->lastname),
            'password' => $owner->password,
            'belongs_to' => $owner->id,
            'country_id' => $owner->country_id,
            'status_id' => is_null($status_intermediate) ? null : $status_intermediate->id,
            'autoplay_enabled' => $request->has('autoplay_enabled') ? filter_var($request->autoplay_enabled, FILTER_VALIDATE_BOOLEAN) : true,
            'preferred_themes' => $this->themes($request->preferred_themes) ?? [],
        ]);

        $role_member = Role::where('role_name', 'Membre')->first();

        if (!is_null($role_member)) {
            $child->roles()->attach([$role_member->id]);
        }

        return $this->handleResponse(new ResourcesProfile($child->fresh()), __('notifications.create_child_success'));
    }

    /**
     * Update a child profile (identity, autoplay, preferred themes).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $owner = $request->user();

        if ($error = $this->checkParentAccess($request, $owner)) {
            return $error;
        }

        $child = User::where([['id', $id], ['belongs_to', $owner->id]])->first();

        if (is_null($child)) {
            return $this->handleError(__('notifications.profile_not_in_family'), [], 404);
        }

        $validator = Validator::make($request->all(), $this->childRules(false));

        if ($validator->fails()) {
            return $this->handleError($validator->errors(), __('notifications.child_invalid'), 400);
        }

        $fields = [];

        foreach (['firstname', 'lastname', 'surname', 'gender', 'birth_date'] as $field) {
            if ($request->has($field)) {
                $fields[$field] = is_string($request->input($field)) ? trim($request->input($field)) : $request->input($field);
            }
        }

        if ($request->has('autoplay_enabled')) {
            $fields['autoplay_enabled'] = filter_var($request->autoplay_enabled, FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->has('preferred_themes')) {
            $fields['preferred_themes'] = $this->themes($request->preferred_themes) ?? [];
        }

        if (!empty($fields)) {
            $child->update(array_merge($fields, ['updated_at' => now()]));
        }

        return $this->handleResponse(new ResourcesProfile($child->fresh()), __('notifications.update_child_success'));
    }

    /**
     * Delete a child profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $owner = $request->user();

        if ($error = $this->checkParentAccess($request, $owner)) {
            return $error;
        }

        $child = User::where([['id', $id], ['belongs_to', $owner->id]])->first();

        if (is_null($child)) {
            return $this->handleError(__('notifications.profile_not_in_family'), [], 404);
        }

        $child->tokens()->delete();
        $child->delete();

        $children = User::where('belongs_to', $owner->id)->orderBy('id')->get();

        return $this->handleResponse(ResourcesProfile::collection($children), __('notifications.delete_child_success'));
    }

    // ==================================== HELPERS ====================================

    /**
     * Parental settings : only the parent account, with a valid parental code.
     */
    private function checkParentAccess(Request $request, User $current)
    {
        if ($current->belongs_to != null) {
            return $this->handleError(__('notifications.only_parent_allowed'), [], 403);
        }

        if (empty($current->parental_code)) {
            return $this->handleError(__('notifications.parental_code_required'), [], 422);
        }

        if (!$this->codeMatches($current, $request->parental_code)) {
            return $this->handleError(__('notifications.parental_code_invalid'), [], 403);
        }

        return null;
    }

    private function codeMatches(User $owner, $code): bool
    {
        return !empty($owner->parental_code) && is_scalar($code) && hash_equals((string) $owner->parental_code, (string) $code);
    }

    private function isInFamily(User $owner, User $target): bool
    {
        return $target->id == $owner->id || $target->belongs_to == $owner->id;
    }

    private function childRules(bool $creating): array
    {
        return [
            'firstname' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:M,F,H'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'preferred_themes' => ['nullable', 'array'],
            'preferred_themes.*' => ['in:' . implode(',', self::PREFERENCE_THEMES)],
        ];
    }

    private function themes($themes): ?array
    {
        if (!is_array($themes)) {
            return null;
        }

        return array_values(array_unique(array_intersect($themes, self::PREFERENCE_THEMES)));
    }

    private function uniqueUsername(string $firstname, ?string $lastname): string
    {
        $base = Str::slug(trim($firstname . ' ' . ($lastname ?? '')), '_') ?: 'enfant';
        $base = Str::limit($base, 36, '');

        do {
            $username = $base . '_' . strtolower(Random::generate(4, '0-9a-z'));
        } while (User::where('username', $username)->exists());

        return $username;
    }
}
