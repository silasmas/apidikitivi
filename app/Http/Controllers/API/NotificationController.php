<?php

namespace App\Http\Controllers\API;

use App\Models\Notification;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Notification as ResourcesNotification;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class NotificationController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $notifications = Notification::orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesNotification::collection($notifications), __('notifications.find_all_notifications_success'));
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
            'notification_url' => $request->notification_url,
            'notification_content' => $request->notification_content,
            'notif_name' => $request->notif_name,
            'status_id' => $request->status_id,
            'user_id' => $request->user_id
        ];

        $validator = Validator::make($inputs, [
            'notification_url' => ['required'],
            'notification_content' => ['required'],
            'user_id' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->handleError($validator->errors());       
        }

        $notification = Notification::create($inputs);

        return $this->handleResponse(new ResourcesNotification($notification), __('notifications.create_notification_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $notification = Notification::find($id);

        if (is_null($notification)) {
            return $this->handleError(__('notifications.find_notification_404'));
        }

        return $this->handleResponse(new ResourcesNotification($notification), __('notifications.find_notification_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Notification  $notification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Notification $notification)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'notification_url' => $request->notification_url,
            'notification_content' => $request->notification_content,
            'notif_name' => $request->notif_name,
            'status_id' => $request->status_id,
            'user_id' => $request->user_id,
            'updated_at' => now()
        ];

        $validator = Validator::make($inputs, [
            'notification_url' => ['required'],
            'notification_content' => ['required'],
            'user_id' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->handleError($validator->errors());       
        }

        $notification->update($inputs);

        return $this->handleResponse(new ResourcesNotification($notification), __('notifications.update_notification_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Notification  $notification
     * @return \Illuminate\Http\Response
     */
    public function destroy(Notification $notification)
    {
        $notification->delete();

        $notifications = Notification::all();

        return $this->handleResponse(ResourcesNotification::collection($notifications), __('notifications.delete_notification_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Select all user notifications.
     *
     * @param  $user_id
     * @return \Illuminate\Http\Response
     */
    public function selectByUser($user_id)
    {
        $notifications = Notification::where('user_id', $user_id)->get();

        return $this->handleResponse(ResourcesNotification::collection($notifications), __('notifications.find_all_notifications_success'));
    }

    /**
     * Change notification status.
     *
     * @param  $id
     * @param  $status_id
     * @return \Illuminate\Http\Response
     */
    public function switchStatus($id, $status_id)
    {
        $status = Status::find($status_id);

        if (is_null($status)) {
            return $this->handleError(__('notifications.find_status_404'));
        }

        $notification = Notification::find($id);

        // update "status_id" column
        $notification->update([
            'status_id' => $status->id,
            'updated_at' => now()
        ]);

        return $this->handleResponse(new ResourcesNotification($notification), __('notifications.find_notification_success'));
    }

    /**
     * Change notification status.
     *
     * @param  $user_id
     * @return \Illuminate\Http\Response
     */
    public function markAllRead($user_id)
    {
        $status_read = Status::where('status_name', 'Lue')->first();
        $notifications = Notification::where('user_id', $user_id)->get();

        // update "status_id" column for all user notifications
        foreach ($notifications as $notification):
            $notification->update([
                'status_id' => $status_read->id,
                'updated_at' => now()
            ]);
        endforeach;

        return $this->handleResponse(ResourcesNotification::collection($notifications), __('notifications.find_all_notifications_success'));
    }
}
