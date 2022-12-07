<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Edujugon\PushNotification\PushNotification;
use App\Models\UserDevice;
use App\Models\PushNotifications;
use App\Models\NotificationLog;
use Pushok\AuthProvider\Token;
use Pushok\Client;
use Pushok\Payload;
use Pushok\Payload\Alert;

/**
 * Replace null values with empty string
 *
 * @param array $array
 * @param array $other_params (Optional)
 * @return  array   $array
 */
function replace_null_with_empty_string($array, $other_params = array())
{

    $array = collect($array)->toArray();
    // Get params which we need to exclude from creating object
    $exclude_array = $other_params['exclude_array'] ?? array();

    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (empty($value)) {
                if (!in_array($key, $exclude_array)) {
                    $array[$key] = (object)[];
                } else {
                    $array[$key] = [];
                }
            } else {
                $array[$key] = replace_null_with_empty_string($value, $other_params);
            }
        } else {
            if (is_null($value))
                $array[$key] = "";
        }
    }

    return $array;
}

function replace_null_with_empty_string_custom($array, $keyt)
{

    $array = collect($array)->toArray();

    foreach ($array as $key => $value) {
        if ($key == $keyt) {
            if (is_array($value)) {
                if (empty($value)) {
                    $array[$key] = (object)[];
                } else {
                    $array[$key] = replace_null_with_empty_string($value);
                }
            } else {
                if (is_null($value))
                    $array[$key] = "";
            }
        }
    }

    return $array;
}

function RandomPassword($length)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*_";
    $password = substr(str_shuffle($chars), 0, $length);
    return $password;
}

function HashPassword($password)
{
    return Hash::make($password);
}

function getFormatedDate($date, $format = NULL)
{
    $return = '';
    if (isset($date) && !empty($date) && $date != null) {
        if (!isset($format)) {
            $format = 'Y-m-d H:i:s';
        }
        return Carbon::parse($date)->format($format);
    }
    return $return;
}

function getDbCompetibleDateFormat($date)
{
    return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d H:i:s');

}

function moneyFormat($value)
{
    return number_format($value, 2);
}

function assetsPath()
{
    return asset('/');
}

function getProgress($user)
{
    $total = 0;
    $date_array = array();
    $profile = User::with(['certificate', 'careerHistory', 'seekerDetails', 'qualification', 'skills', 'locations', 'interests'])->where('id', $user->id)->first();
    $date_array[] = $profile->updated_at;
    if (isset($profile->profile_picture))
        $total += config('job-seekers.progress.profile_image');
    if ($profile->phone_verified == 1)
        $total += config('job-seekers.progress.phone_verified');

    if (isset($profile->seekerDetails)) {
        if (isset($profile->seekerDetails->resume))
            $total += config('job-seekers.progress.resume');
        if (isset($profile->seekerDetails->summary))
            $total += config('job-seekers.progress.summary');
        if (isset($profile->seekerDetails->preference_id))
            $total += config('job-seekers.progress.preference');
        $date_array[] = $profile->seekerDetails->updated_at;
    }

    if (!$profile->certificate->isEmpty()) {
        $total += config('job-seekers.progress.certificates');
        $date_array[] = $profile->certificate[0]->updated_at;
    }

    if (!$profile->careerHistory->isEmpty()) {
        $total += config('job-seekers.progress.career_history');
        $date_array[] = $profile->careerHistory[0]->updated_at;
    }
    if (!$profile->qualification->isEmpty()) {
        $total += config('job-seekers.progress.qualification');
        $date_array[] = $profile->qualification[0]->updated_at;
    }
    if (!$profile->skills->isEmpty()) {
        $total += config('job-seekers.progress.skills');
        $date_array[] = $profile->skills[0]->updated_at;
    }
    if (!$profile->locations->isEmpty()) {
        $total += config('job-seekers.progress.locations');
        $date_array[] = $profile->locations[0]->updated_at;
    }
    if (isset($profile->interests)) {
        $total += config('job-seekers.progress.interests');
        $date_array[] = $profile->interests->updated_at;
    }
    $maxdate = max(array_map('strtotime', $date_array));
    $updated_at = time_elapsed_string(date('Y-m-j H:i:s', $maxdate));

    return array('progress' => $total, 'updated_at' => $updated_at);
}

if (!function_exists('time_format_half')) {
    /**
     * @param $datetime
     * @return false|string
     */
    function time_format_half($datetime)
    {
        return date('h:i A', strtotime($datetime));
    }
}

function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

if (!function_exists('ActiveRoutes')) {
    function ActiveRoutes($routeName, $class = 'active')
    {
        $currentRoute = Route::currentRouteName();

        if (in_array($currentRoute, $routeName)) {
            return 'active';
        } else {

            return '';
        }

    }
}
function rudr_mailchimp_subscriber_status($email, $status, $list_id, $api_key, $merge_fields = array('FNAME' => '', 'FPHONE' => '', 'FMSG' => ''))
{
    $data = array(
        'apikey' => $api_key,
        'email_address' => $email,
        'status' => $status,
        'merge_fields' => $merge_fields
    );
    $mch_api = curl_init(); // initialize cURL connection

    curl_setopt($mch_api, CURLOPT_URL, 'https://' . substr($api_key, strpos($api_key, '-') + 1) . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($data['email_address'])));
    curl_setopt($mch_api, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic ' . base64_encode('user:' . $api_key)));
    curl_setopt($mch_api, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
    curl_setopt($mch_api, CURLOPT_RETURNTRANSFER, true); // return the API response
    curl_setopt($mch_api, CURLOPT_CUSTOMREQUEST, 'PUT'); // method PUT
    curl_setopt($mch_api, CURLOPT_TIMEOUT, 10);
    curl_setopt($mch_api, CURLOPT_POST, true);
    curl_setopt($mch_api, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($mch_api, CURLOPT_POSTFIELDS, json_encode($data)); // send data in json

    $result = curl_exec($mch_api);
    //return $result;
}

/**
 * Send Push Notification
 *
 * @param array $array
 * @return  void
 */
function sendNotificationFcm($tokens, $title, $msg, $type, $data)
{
    if(empty($tokens)){
        return true;
    }
    $push = new PushNotification('fcm');
    $push->setMessage([
        'notification' => [
            'title' => $title,
            'body' => $msg,
            'sound' => 'default'
        ],
        'data' => [
            'notification_type' => $type,
            'data' => $data,
        ]
    ])
        ->setApiKey(env('SERVER_KEY'))
        ->setDevicesToken($tokens)
        ->send();
        \Log::info([$push->getFeedback()]);

    return response()->json($push->getFeedback());
}

function storeNotification($data)
{
    try {
        $chkAlreadyExt = PushNotifications::where('user_id', $data['user_id'])->where('type', $data['type'])->whereJsonContains('data', ['appointment_id' => $data['data']['appointment_id']])->first();

        if (!is_null($chkAlreadyExt)) {
            PushNotifications::where('user_id', $data['user_id'])
                ->update([
                    'data' => isset($data['data']) ? $data['data'] : $data,
                    'message' => $data['message'],
                    'date' => date('Y-m-d H:i:s')
                ]);

        }
        if (is_null($chkAlreadyExt)) {
            PushNotifications::create($data);
            NotificationLog::create($data);
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function sendNotificationApn($deviceTokens, $title, $msg, $type, $data)
{
    if(empty($deviceTokens)){
        return true;
    }
    \Log::info(['tokens----' => $deviceTokens]);
    $options = [
        'key_id' => 'UGJ6M9RFQ4',
        'team_id' => '4NRM49YQ37',
        'app_bundle_id' => 'com.clickthruhealth',
        'private_key_path' => asset('AuthKey_UGJ6M9RFQ4.p8'),
        'private_key_secret' => null // Private key secret
    ];

    $authProvider = Token::create($options);


    $alert = Alert::create()->setTitle($title);
    $alert = $alert->setBody($msg);

    $payload = Payload::create()->setAlert($alert);
    // if(isset($data['sound']) && $data['sound']== 'default.wave'){
    //     \Log::info('sound');
    //      $payload->setSound('default.wave');
    // }else{
    //     \Log::info('not sound');
    // $payload->setSound('default');
    // }
    $payload->setSound('default.wave');
    $payload->setCustomValue('notification_type', $type);
    $payload->setCustomValue('data', $data);

    $notifications = [];

    $notifications[] = new \Pushok\Notification($payload, $deviceTokens);

    // $client = new Client($authProvider, $production = false);
     $client = new Client($authProvider, $production = true);
    $client->addNotifications($notifications);

    $responses = $client->push();
    $respData = [];
    foreach ($responses as $response) {
        // The device token
        $respData[] = $response->getDeviceToken();
        $respData[] = $response->getApnsId();
        $respData[] = $response->getStatusCode();
        $respData[] = $response->getReasonPhrase();
        $respData[] = $response->getErrorReason();
        $respData[] = $response->getErrorDescription();
        $respData[] = $response->get410Timestamp();
    }

    return response()->json($respData);

}


?>
