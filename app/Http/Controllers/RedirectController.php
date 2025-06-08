<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function handle(string $short_code)
    {
        $shortUrl = ShortUrl::where('short_code', $short_code)->firstOrFail();
        return redirect($shortUrl->long_url);
    }
}
