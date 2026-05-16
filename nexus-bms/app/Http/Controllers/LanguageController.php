<?php
namespace App\Http\Controllers;

class LanguageController extends Controller
{
    public function switch(string $locale)
    {
        if (in_array($locale, ['th', 'en'])) {
            session(['locale' => $locale]);
            auth()->user()?->update(['locale' => $locale]);
        }
        return redirect()->back();
    }
}
