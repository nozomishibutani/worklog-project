<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\AttendanceFormatterService;

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
            'work_in' => 'bail|nullable|required_with:work_out|date_format:H:i',
            'work_out' => 'bail|nullable|date_format:H:i',
            'break_in.*'  => 'bail|nullable|required_with:break_out.*|date_format:H:i',
            'break_out.*' => 'bail|nullable|date_format:H:i',
            'note' => 'required|max:255',
        ];
    }
    public function messages()
    {
        return [
            'work_in.required_with' => '出勤時間を入力してください',
            'work_in.date_format' => '出勤時間が不適切な値です',
            'work_out.date_format' => '退勤時間が不適切な値です',
            'break_in.*.required_with' => '休憩入り時間を入力してください',
            'break_in.*.date_format:H:i' => '休憩戻り時間が不適切な値です',
            'break_out.*.date_format:H:i' => '休憩入り時間が不適切な値です',
            'note.required' => '備考を記入してください',
            'note.max' => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $attendanceFormatterService = app(AttendanceFormatterService::class);
            $isInvalidBreakTimes = [];
            $breakTimes = [];
            $workDate = [
                'year' => $this->year,
                'month' => $this->month,
                'day' => $this->day,
            ];
            $carbonWorkIn = $this->work_in ? $attendanceFormatterService->formatCarbonDate($workDate, $this->work_in) : null;
            $carbonWorkOut = $this->work_out ? $attendanceFormatterService->formatCarbonDate($workDate, $this->work_out) : null;

            // 1. 出勤時間が退勤時間より後になっている場合，および退勤時間が出勤時間より前になっている場合に以下のメッセージを表示
            if (!is_null($carbonWorkIn) && !is_null($carbonWorkOut) && $carbonWorkIn >= $carbonWorkOut) {
                if (!$validator->errors()->has('work_in') && !$validator->errors()->has('work_out')) {
                    $validator->errors()->add('work_in', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            // 2. 休憩開始時間が出勤時間より前になっている場合及び退勤時間より後になっている場合、以下のメッセージを表示
            foreach ($this->break_in as $id => $breakIn) {
                if (is_null($breakIn)) {
                    continue;
                }
                $CarbonBreakIn[$id] = $attendanceFormatterService->formatCarbonDate($workDate, $breakIn);
                $CarbonBreakOut[$id] =  $this->break_out[$id] ? $attendanceFormatterService->formatCarbonDate($workDate, $this->break_out[$id]) : null;
                $breakTimes[$breakIn] = [
                    'id' => $id,
                    'break_in' =>  $CarbonBreakIn[$id],
                    'break_out' => $CarbonBreakOut[$id],
                ];
            }
            if (empty($breakTimes)) {
                // 休憩レコードが存在しない
                return;
            }
            ksort($breakTimes);

            foreach ($CarbonBreakIn as $id => $breakIn) {
                if ($id === 0 && is_null($breakIn)) {
                    continue;
                }
                // 休憩開始時間が出勤時間より前になっている場合及び退勤時間より後になっている場合
                if ($breakIn <= $carbonWorkIn) {
                    $isInvalidBreakTimes[$id] = true;
                } elseif (!is_null($carbonWorkOut) && $breakIn >= $carbonWorkOut) {
                    $isInvalidBreakTimes[$id] = true;
                }
            }

            // 3.休憩終了時間が退勤時間より後になっている場合、以下のメッセージを表示
            foreach ($CarbonBreakOut as $id => $breakOut) {
                if (is_null($carbonWorkOut)) {
                    break;
                }
                if (is_null($breakOut)) {
                    // 退勤時間が入力済みなのに休憩戻り時間がない場合に以下のメッセージを表示
                    $validator->errors()->add('break_out.'. $id, '休憩時間もしくは退勤時間が不適切な値です');
                }
                if ($breakOut >= $carbonWorkOut && empty($isInvalidBreakTimes[$id])) {
                    $validator->errors()->add('break_in.'. $id, '休憩時間もしくは退勤時間が不適切な値です');
                }
            }

            // 休憩時間のバリデーション
            $tmp = null;
            $loopCount = 0;
            $breakTimeCount = count($breakTimes);

            foreach ($breakTimes as $breakIn => $breakTime) {
                $loopCount++;
                foreach ($breakTime as $key => $val) {
                    if ($key == 'id') {
                        $id = $val;
                        if (is_null($breakTime['break_out']) && $loopCount !== $breakTimeCount) {
                            // 最後の休憩戻り以外が空だった場合に以下のメッセージを表示
                            $validator->errors()->add('break_out.'. $id, '休憩戻り時間を入力してください');
                            break 2;
                        } elseif (!is_null($breakTime['break_out']) && $breakTime['break_in'] >= $breakTime['break_out']) {
                            //休憩入り時間が休憩戻り時間より後になっている場合、および休憩戻り時間が休憩入り時間より前になっている場合に以下のメッセージを表示
                            $isInvalidBreakTimes[$id] = true;
                            break 2;
                        }
                        continue;
                    }
                    if (is_null($tmp) && $key == 'break_in') {
                        // 最初の一回
                        $tmp = $val;
                        continue;
                    }
                    if ($tmp < $val) {
                        // 休憩入り時間 < 休憩戻り時間 < 休憩入り時間
                        $tmp = $val;
                    } elseif (!is_null($breakTime['break_out'])) {
                        // 重複が発生
                        $validator->errors()->add('break_in.'. $id, '他の休憩時間と重複しています');
                        break;
                    }
                }
            }
            foreach ($isInvalidBreakTimes as $id => $isInvalidBreakTime) {
                if ($isInvalidBreakTime && !$validator->errors()->has('break_in.'. $id) && !$validator->errors()->has('break_out.'. $id)) {
                    // エラーメッセージは一回だけ表示
                    $validator->errors()->add('break_in.'. $id, '休憩時間が不適切な値です');
                }
            }
        });
    }
}
