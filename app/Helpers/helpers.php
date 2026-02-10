<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('get_setting')) {
    function get_setting($type, $key) {
        $setting = DB::table('settings')
            ->where('type', $type)
            ->first();
        
        if (!$setting) {
            return null;
        }
        
        $data = json_decode($setting->value);
        return $data->{$key} ?? null;
    }
}

if (!function_exists('company_name')) {
    function company_name() {
        return get_setting('general', 'company_name') ?? 'Seer Dynamics';
    }
}

if (!function_exists('favicon')) {
    function favicon() {
        return get_setting('general', 'favicon') ?? 'favicon.ico';
    }
}

if (!function_exists('smtp_email')) {
    function smtp_email() {
        return get_setting('general', 'smtp_email') ?? '';
    }
}

if (!function_exists('smtp_username')) {
    function smtp_username() {
        return get_setting('general', 'smtp_username') ?? '';
    }
}

if (!function_exists('footer_text')) {
    function footer_text() {
        return get_setting('general', 'footer_text') ?? '© ' . date('Y') . ' Seer Dynamics';
    }
}

if (!function_exists('google_analytics')) {
    function google_analytics() {
        return get_setting('general', 'google_analytics') ?? '';
    }
}

if (!function_exists('email_activation')) {
    function email_activation() {
        return get_setting('general', 'email_activation') == '1';
    }
}

if (!function_exists('get_currency')) {
    function get_currency($type) {
        $value = get_setting('general', $type);
        
        if ($value) {
            return $value;
        }
        
        return $type == 'currency_code' ? 'USD' : '$';
    }
}

if (!function_exists('permissions')) {
    function permissions($permissions_type = '') {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }
        
        $type = 'permissions';
        if ($user->inGroup(3)) {
            $type = 'clients_permissions';
        } elseif ($user->inGroup(4)) {
            $type = 'cuser_permissions';
        }
        
        $setting = DB::table('settings')->where('type', $type)->first();
        
        if (!$setting) {
            return false;
        }
        
        $data = json_decode($setting->value);
        
        if (empty($permissions_type)) {
            return $data;
        }
        
        return $data->{$permissions_type} ?? false;
    }
}

if (!function_exists('company_details')) {
    function company_details($type = '', $user_id = '') {
        if (empty($user_id)) {
            $user_id = auth()->id();
        }
        
        $where_type = 'company_' . $user_id;
        $setting = DB::table('settings')->where('type', $where_type)->first();
        
        if (!$setting) {
            return '';
        }
        
        $data = json_decode($setting->value);
        
        if ($type == '') {
            return $data;
        }
        
        return $data->{$type} ?? '';
    }
}