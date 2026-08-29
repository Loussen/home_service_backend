<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class WebAppController extends Controller
{
    public function index(): View
    {
        return view('web.dashboard');
    }

    public function login(): View
    {
        return view('web.login');
    }

    public function request(): View
    {
        return view('web.request');
    }

    public function onboarding(): View
    {
        return view('web.onboarding');
    }

    public function categories(): View
    {
        return view('web.categories');
    }

    public function profile(): View
    {
        return view('web.profile');
    }

    public function chat(): View
    {
        return view('web.chat');
    }

    public function chatShow(int $id): View
    {
        return view('web.chat-thread', ['conversationId' => $id]);
    }

    public function jobs(): View
    {
        return view('web.jobs');
    }
}

