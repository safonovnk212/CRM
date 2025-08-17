<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class IntakeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            "name"      => ["nullable","string","max:255"],
            "phone"     => ["required","string","max:64"],
            "offer_id"  => ["nullable","string","max:255"],
            "click_id"  => ["nullable","string","max:255"],
            "hp_field"  => ["nullable","string","max:255"],
            "utm_source"=> ["nullable","string","max:255"],
            "utm_medium"=> ["nullable","string","max:255"],
            "utm_campaign"=>["nullable","string","max:255"],
            "utm_content"=>["nullable","string","max:255"],
            "utm_term"  => ["nullable","string","max:255"],
            "sub1"      => ["nullable","string","max:255"],
            "sub2"      => ["nullable","string","max:255"],
            "sub3"      => ["nullable","string","max:255"],
            "sub4"      => ["nullable","string","max:255"],
            "sub5"      => ["nullable","string","max:255"],
            "fbclid"    => ["nullable","string","max:256"],
            "fbp"       => ["nullable","string","max:256"],
            "fbc"       => ["nullable","string","max:256"],
        ];
    }
}
