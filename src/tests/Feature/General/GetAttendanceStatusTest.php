<?php

namespace Tests\Feature\Auth;

use App\Enums\attendanceStatus;
use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GetAttendanceStatusTest extends TestCase
{
    /**
     * ステータス確認機能
     */
    use RefreshDatabase;

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    #[Test]
    public function offStatusIsDisplayed()
    {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);

        // 1. ステータスが勤務外のユーザーにログインする
        // 2. 勤怠打刻画面を開く
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
        ]);
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertStatus(200);

        // 3. 画面に表示されているステータスを確認する
        $response->assertSee(attendanceStatus::OFF->label());
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    #[Test]
    public function onDutyStatusIsDisplayed()
    {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);

        $now = now();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが出勤中のユーザーにログインする
        // 2. 勤怠打刻画面を開く
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => $now->format('Y-m-d'),
            'clock_in' => $now->format('Y-m-d H:i:s'),
        ]);
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertStatus(200);

        // 3. 画面に表示されているステータスを確認する
        $response->assertSee(attendanceStatus::ON_DUTY->label());
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    #[Test]
    public function onBreakStatusIsDisplayed()
    {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);

        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->copy()->subMinute(5)->format('Y-m-d H:i:s'),
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'clock_in' => $now->copy()->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが休憩中のユーザーにログインする
        // 2. 勤怠打刻画面を開く
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => $now->format('Y-m-d'),
            'clock_in' => $now->copy()->subMinute(5)->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'clock_in' => $now->copy()->format('Y-m-d H:i:s'),
        ]);
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertStatus(200);

        // 3. 画面に表示されているステータスを確認する
        $response->assertSee(attendanceStatus::ON_BREAK->label());
    }

    /**
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    #[Test]
    public function offDUTYStatusIsDisplayed()
    {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);

        $now = now();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->copy()->subHours(6)->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが退勤済のユーザーにログインする
        // 2. 勤怠打刻画面を開く
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => $now->format('Y-m-d'),
            'clock_in' => $now->copy()->subHours(6)->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
        ]);
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertStatus(200);

        // 3. 画面に表示されているステータスを確認する
        $response->assertSee(attendanceStatus::OFF_DUTY->label());
    }
}
