<?php

namespace Tests\Feature\Auth;

use App\Enums\attendanceStatus;
use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Services\AttendanceCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class BreakTimeOperationTest extends TestCase
{
    /**
     * 休憩機能
     */
    use RefreshDatabase;

    /**
     * 休憩ボタンが正しく機能する
     */
    #[Test]
    public function onBreakTimeButtonIsWorking() {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->copy()->subHours(1)->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが出勤中のユーザーにログインする
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertSee(attendanceStatus::ON_DUTY->label());

        // 2. 画面に「休憩入」ボタンが表示されていることを確認する
        $response->assertSee('休憩入</button>', false);

        // 3. 休憩の処理を行う
        $response =  $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::ON_BREAK->value,
        ]);

        // 画面上に「休憩入」ボタンが表示され、処理後に画面上に表示されるステータスが「休憩中」になる
        $response = $this->get(route('index'));
        $response->assertStatus(200);
        $response->assertSee(attendanceStatus::ON_BREAK->label());
    }

    /**
     * 休憩は一日に何回でもできる
     */
    #[Test]
    public function userCanSubmitOnBreakTimeManyTimes() {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->copy()->subHours(1)->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが出勤中のユーザーにログインする
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertSee(attendanceStatus::ON_DUTY->label());

        // 2. 休憩入と休憩戻の処理を行う
        $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::ON_BREAK->value,
        ]);
        $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::OFF_BREAK->value,
        ]);

        // 3.「休憩入」ボタンが表示されることを確認する
        // 画面上に「休憩入」ボタンが表示される
        $response = $this->get(route('index'));
        $response->assertStatus(200);
        $response->assertSee('休憩入</button>', false);
    }

    /**
     * 休憩戻ボタンが正しく機能する
     */
    #[Test]
    public function offBreakTimeButtonIsWorking() {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->copy()->subHours(1)->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが出勤中のユーザーにログインする
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertSee(attendanceStatus::ON_DUTY->label());

        // 2. 休憩入の処理を行う
        $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::ON_BREAK->value,
        ]);
        sleep(1);

        // 3. 休憩戻の処理を行う
        $response = $this->get(route('index'));
        $response->assertStatus(200);
        $response->assertSee('休憩戻</button>', false);
        $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::OFF_BREAK->value,
        ]);

        // 休憩戻ボタンが表示され、処理後にステータスが「出勤中」に変更される
        $response = $this->get(route('index'));
        $response->assertStatus(200);
        $response->assertSee(attendanceStatus::ON_DUTY->label());
    }

    /**
     * 休憩戻は一日に何回でもできる
     */
    #[Test]
    public function userCanSubmitOffBreakTimeManyTimes() {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->copy()->subHours(1)->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが出勤中のユーザーにログインする
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertSee(attendanceStatus::ON_DUTY->label());

        // 2. 休憩入と休憩戻の処理を行い、再度休憩入の処理を行う
        $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::ON_BREAK->value,
        ]);
        sleep(1);
        $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::OFF_BREAK->value,
        ]);
        sleep(1);
        $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::ON_BREAK->value,
        ]);

        // 3.「休憩戻」ボタンが表示されることを確認する
        // 画面上に「休憩戻」ボタンが表示される
        $response = $this->get(route('index'));
        $response->assertStatus(200);
        $response->assertSee('休憩戻</button>', false);
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    #[Test]
    public function userCanCheckAttendanceList() {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->copy()->subHours(1)->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが勤務（出勤）中のユーザーにログインする
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertSee(attendanceStatus::ON_DUTY->label());

        // 2. 休憩入と休憩戻の処理を行う
        $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::ON_BREAK->value,
        ]);

        $this->post(route('log'), [
            'attendance_id' => $attendance->id,
            'action' => attendanceStatus::OFF_BREAK->value,
        ]);

        // 3.勤怠一覧画面から休憩の日付を確認する
        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        $startOfMonth = Carbon::createFromFormat('Ymd', $now->copy()->format('Ym') . '01')->startOfMonth();

        [
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ] = $attendanceCalculatorService->getUserMonthlyAttendances($user->id, $startOfMonth);

        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 勤怠一覧画面に休憩時刻が正確に記録されている
        $response->assertSeeInOrder([
            $workTimes[$now->copy()->format('Ymd')]['display_date'],
            $workTimes[$now->copy()->format('Ymd')]['clock_in'],
            $breakTimes[$now->copy()->format('Ymd')]['display_total'],
        ]);
    }
}