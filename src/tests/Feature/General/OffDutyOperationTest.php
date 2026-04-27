<?php

namespace Tests\Feature\General;

use App\Enums\attendanceStatus;
use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class OffDutyOperationTest extends TestCase
{
    /**
     * 退勤機能
     */
    use RefreshDatabase;

    /**
     * 退勤ボタンが正しく機能する
     */
    #[Test]
    public function attendanceButtonIsWorking()
    {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->copy()->subMinute()->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが勤務（出勤）中のユーザーにログインする
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertSee(attendanceStatus::ON_DUTY->label());

        // 2. 画面に「退勤」ボタンが表示されていることを確認する
        $response->assertSee('退勤</button>', false);

        // 3. 退勤の処理を行う
        $response =  $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::OFF->value,
        ]);

        // 画面上に「退勤」ボタンが表示され、処理後に画面上に表示されるステータスが「退勤済」になる
        $response = $this->get(route('index'));
        $response->assertStatus(200);
        $response->assertSee(attendanceStatus::OFF_DUTY->label());
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    #[Test]
    public function userCanCheckAttendanceList()
    {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);
        $now = now();

        // 1. ステータスが勤務外のユーザーにログインする
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
        ]);
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertSee(attendanceStatus::OFF->label());

        // 2. 出勤と退勤の処理を行う
        $this->post(route('log'), [
            'attendance_id' => '',
            'action' => attendanceStatus::ON_DUTY->value,
        ]);
        $attendance = Attendance::where('user_id', $user->id)->Where('work_date', $now->format('Y-m-d'))->first();
        $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::OFF->value,
        ]);

        // 3.勤怠一覧画面から退勤の日付を確認する
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 勤怠一覧画面に退勤時刻が正確に記録されている
        $response->assertSeeInOrder([
            $now->copy()->format('m/d') . '(' . $now->isoFormat('ddd') . ')',
            $now->copy()->format('H:i'), // 出勤
            $now->copy()->format('H:i'), // 退勤
        ]);
    }
}
