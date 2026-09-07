<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Application Configuration
|--------------------------------------------------------------------------
|
| Laravel merges the framework's own config/app.php underneath this file, so
| only the keys this application adds or overrides need to live here.
|
*/

return [

    /*
    | Locales the UI, the validation messages and the translatable JSON columns
    | are available in. Read by App\Http\Middleware\SetLocale and by
    | App\Traits\HasTranslations::scopeWhereTranslationLike.
    */

    'supported_locales' => ['en', 'ru'],

];
