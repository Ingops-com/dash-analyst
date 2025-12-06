<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class ReminderController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reminders = Reminder::where('user_id', auth()->id())
            ->orderBy('reminder_date', 'asc')
            ->paginate(10);

        return Inertia::render('Reminders/Index', [
            'reminders' => $reminders,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Reminders/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reminder_date' => 'required|date',
        ]);

        // Convertir el formato datetime-local (YYYY-MM-DDTHH:mm) a formato MySQL
        if (isset($validated['reminder_date'])) {
            $validated['reminder_date'] = str_replace('T', ' ', $validated['reminder_date']) . ':00';
        }

        Reminder::create([
            'user_id' => auth()->id(),
            ...$validated,
        ]);

        return redirect()->route('reminders.index')
            ->with('message', 'Recordatorio creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Reminder $reminder)
    {
        $this->authorize('view', $reminder);

        return Inertia::render('Reminders/Show', [
            'reminder' => $reminder,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reminder $reminder)
    {
        $this->authorize('update', $reminder);

        return Inertia::render('Reminders/Edit', [
            'reminder' => $reminder,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reminder $reminder)
    {
        $this->authorize('update', $reminder);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reminder_date' => 'required|date',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        // Convertir el formato datetime-local (YYYY-MM-DDTHH:mm) a formato MySQL
        if (isset($validated['reminder_date'])) {
            $validated['reminder_date'] = str_replace('T', ' ', $validated['reminder_date']) . ':00';
        }

        $reminder->update($validated);

        return redirect()->route('reminders.index')
            ->with('message', 'Recordatorio actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reminder $reminder)
    {
        $this->authorize('delete', $reminder);

        $reminder->delete();

        return redirect()->route('reminders.index')
            ->with('message', 'Recordatorio eliminado exitosamente');
    }

    /**
     * Get reminders for calendar view
     */
    public function calendar()
    {
        $reminders = Reminder::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->get();

        return response()->json($reminders);
    }

    /**
     * Get user notifications
     */
    public function notifications()
    {
        $notifications = auth()->user()
            ->notifications()
            ->where('type', 'App\Notifications\ReminderNotification')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId)
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}

