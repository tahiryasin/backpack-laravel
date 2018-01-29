<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Setting;

class SettingController extends ApiController
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection|Setting[]
     */
    public function __invoke()
    {
        return Setting::all()
            ->filter(function (Setting $setting) {
                return $setting->active && !$setting->is_hidden;
            })
            ->mapWithKeys(function (Setting $setting) {
                if ($setting->related) {
                    return [$setting->key => $setting->item];
                }

                return [$setting->key => $setting->value];
            });
    }
}
