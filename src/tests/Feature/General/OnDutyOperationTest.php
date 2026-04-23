<?php

namespace Tests\Feature\Auth;

use App\Enums\attendanceStatus;
use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class OnDutyOperationTest extends TestCase
{
    /**
     * 出勤機能
     */
    use RefreshDatabase;

    /**
     * 出勤ボタンが正しく機能する
     */
    #[Test]
    public function attendanceButtonIsWorking() {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);

        // 1. ステータスが勤務外のユーザーにログインする
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
        ]);
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertSee(attendanceStatus::OFF->label());

        // 2. 画面に「出勤」ボタンが表示されていることを確認する
        $response->assertSee('出勤</button>', false);

        // 3. 出勤の処理を行う
        $response =  $this->post(route('log'), [
            'attendance_id' => null,
            'action' => attendanceStatus::ON_DUTY->value
        ]);

        // 画面上に「出勤」ボタンが表示され、処理後に画面上に表示されるステータスが「勤務中」になる
        $response = $this->get(route('index'));
        $response->assertSee(attendanceStatus::ON_DUTY->label());
    }

    /**
     * 出勤は一日一回のみできる
     */
    #[Test]
    public function userCanSubmitAttendanceOnlyOncePerDay() {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);

        $now = now();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->copy()->subMinute()->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが退勤済であるユーザーにログインする
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertSee(attendanceStatus::OFF_DUTY->label());

        // 2. 勤務ボタンが表示されないことを確認する
        // 画面上に「出勤」ボタンが表示されない
        $response->assertDontSee('出勤</button>', false);
        $response->assertSee('お疲れ様でした。');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    #[Test]
    public function userCanCheckAttendanceList() {

        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);

        // 1. ステータスが勤務外のユーザーにログインする
        $now = now();
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
        ]);
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertSee(attendanceStatus::OFF->label());

        //2. 出勤の処理を行う
        $response =  $this->post(route('log'), [
            'attendance_id' => null,
            'action' => attendanceStatus::ON_DUTY->value
        ]);

        // 3.勤怠一覧画面から出勤の日付を確認する
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

         // 勤怠一覧画面に出勤時刻が正確に記録されている
        $response->assertSeeInOrder([
            $now->copy()->format('m/d') . '(' . $now->isoFormat('ddd') . ')',
            $now->copy()->format('H:i'),
        ]);
    }
}