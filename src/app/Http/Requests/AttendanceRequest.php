<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'work_in' => ['nullable','date_format:H:i'],
            'work_out' => ['nullable','date_format:H:i', 'after:work_in'],
            'break_in' => ['nullable','after:work_in','before:work_out'],
            'break_in.*' => ['date_format:H:i'],
            'break_out' => ['nullable','before:work_out'],
            'break_out.*' => ['date_format:H:i','after:break_in'],
            'note' => ['required', 'max:255'],
        ];
    }
    public function messages()
    {
        return [
            'work_in.date' => '出勤時間が不適切な値です',
            'work_out.date' => '退勤時間が不適切な値です',
            'work_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'break_in.after' => '休憩時間が不適切な値です',
            'break_in.before' => '休憩時間が不適切な値です',
            'break_in.*.date' => '休憩時間が不適切な値です',
            'break_out.after' => '休憩時間もしくは退勤時間が不適切な値です',
            'break_out.*.date' => '休憩時間が不適切な値です',
            'break_out.*.after' => '休憩時間が不適切な値です',
            'note.require' => '備考を記入してください',
            'note.max' => '備考は255文字以内で入力してください',
        ];
    }

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         if (!$this->email) {
    //             $validator->errors()->add('email', 'メールアドレスはメール形式で入力してください');
    //         }
    //     });
    // }
}
