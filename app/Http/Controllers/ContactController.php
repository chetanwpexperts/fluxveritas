<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show(Request $request)
    {
        $planInterest = $request->get('plan');
        return view('contact', compact('planInterest'));
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
            'company'      => ['nullable', 'string', 'max:255'],
            'message'      => ['required', 'string', 'max:5000'],
            'plan_interest'=> ['nullable', 'string', 'max:50'],
        ]);

        $contact = ContactMessage::create($data);

        try {
            $to = '19chetan87sharma@gmail.com';
            Mail::raw(
                "New contact form submission:\n\n" .
                "Name: {$contact->name}\n" .
                "Email: {$contact->email}\n" .
                "Company: " . ($contact->company ?: '—') . "\n" .
                "Plan interest: " . ($contact->plan_interest ?: '—') . "\n\n" .
                "Message:\n{$contact->message}\n",
                function ($m) use ($to, $contact) {
                    $m->to($to)
                      ->subject('OutraqHQ — New contact: ' . $contact->name)
                      ->replyTo($contact->email, $contact->name);
                }
            );
        } catch (\Throwable $e) {
            // ignore — submission is safely in DB
        }

        return redirect()->route('contact')
            ->with('success', 'Thanks! We received your message and will get back to you soon.');
    }
}
