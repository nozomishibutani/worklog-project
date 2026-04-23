<?php

namespace Tests\Feature\Auth;

use App\Enums\attendanceStatus;
use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use App\Services\AttendanceCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GetAttendanceListTest extends TestCase
{
    /**
     * 勤怠一覧情報取得機能（一般ユーザー）
     */
    use RefreshDatabase;

    /**
     * 自分が行った勤怠情報が全て表示されている
     */
    #[Test]
    public function AllAttendanceAreDisplayed()
    {
        $user = User::factory()->create();
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $now->copy()->format('Y-m-d'),
            'clock_in' => $now->copy()->subMinutes(75)->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->subMinutes(35)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->subMinutes(30)->format('Y-m-d H:i:s'),
        ]);

        // 1. 勤怠情報が登録されたユーザーにログインする
        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);


        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 3. 自分の勤怠情報がすべて表示されていることを確認する
        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        $startOfMonth = Carbon::createFromFormat('Ymd', $now->copy()->format('Ym') . '01')->startOfMonth();

        [
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ] = $attendanceCalculatorService->getUserMonthlyAttendances($user->id, $startOfMonth);

        // 自分の勤怠情報が全て表示されている
        foreach ($workTimes as $workTime) {
            $response->assertSee($workTime['display_date']);
            $response->assertSee($workTime['clock_in']);
            $response->assertSee($workTime['clock_out']);
            if (isset($breakTimes[$now->copy()->format('Ymd')]['display_total'])) {
                $response->assertSee($breakTimes[$now->copy()->format('Ymd')]['display_total']);
            }
            $response->assertSee($workTime['display_total']);
        }
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    #[Test]
    public function currentMonthIsDisplayed()
    {
        $user = User::factory()->create();
        $now = now();

        // 1. ユーザーにログインする
        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠一覧画面ページを開く
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 現在の月が表示されている
        $response->assertSee($now->format('Y/m'));
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    #[Test]
    public function previousMonthIsDisplayed()
    {
        $user = User::factory()->create();
        $now = now();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $now->copy()->startOfMonth()->subMonth()->format('Y-m-d'),
                    'clock_in' => $now->copy()->startOfMonth()->subMonth()->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->startOfMonth()->subMonth()->addHours(8)->format('Y-m-d H:i:s'),
                ]);

        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->startOfMonth()->subMonth()->addHours(6)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->startOfMonth()->subMonth()->addHours(7)->format('Y-m-d H:i:s'),
                ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 3. 「前月」ボタンを押す
        // 前月の情報が表示されている
        $response =  $this->get(route('monthly.index', ['id' => $user->id, 'date' => $now->copy()->startOfMonth()->subMonth()->format('Ym')]));
        $response->assertSee($now->copy()->startOfMonth()->subMonth()->format('Ym'));

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ] = $attendanceCalculatorService->getUserMonthlyAttendances($user->id, $now->copy()->startOfMonth()->subMonth());

        foreach ($workTimes as $workTime) {
            $response->assertSee($workTime['display_date']);
            $response->assertSee($workTime['clock_in']);
            $response->assertSee($workTime['clock_out']);
            if (isset($breakTimes[$now->copy()->startOfMonth()->subMonth()->format('Ymd')]['display_total'])) {
                $response->assertSee($breakTimes[$now->copy()->startOfMonth()->subMonth()->format('Ymd')]['display_total']);
            }
            $response->assertSee($workTime['display_total']);
        }
    }

    /**
     * 「翌月」を押下した時に表示月の前（翌）月の情報が表示される
     */
    #[Test]
    public function nextMonthIsDisplayed()
    {
        $user = User::factory()->create();
        $now = now();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $now->copy()->startOfMonth()->addMonth()->format('Y-m-d'),
                    'clock_in' => $now->copy()->startOfMonth()->addMonth()->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->startOfMonth()->addMonth()->addHours(8.5)->format('Y-m-d H:i:s'),
                ]);

        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->startOfMonth()->addMonth()->addHours(1)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->startOfMonth()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
                ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 3. 「翌月」ボタンを押す
        // 翌月の情報が表示されている
        $response =  $this->get(route('monthly.index', ['id' => $user->id, 'date' => $now->copy()->startOfMonth()->addMonth()->format('Ym')]));
        $response->assertSee($now->copy()->startOfMonth()->addMonth()->format('Ym'));

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ] = $attendanceCalculatorService->getUserMonthlyAttendances($user->id, $now->copy()->startOfMonth()->addMonth());

        foreach ($workTimes as $workTime) {
            $response->assertSee($workTime['display_date']);
            $response->assertSee($workTime['clock_in']);
            $response->assertSee($workTime['clock_out']);
            if (isset($breakTimes[$now->copy()->startOfMonth()->addMonth()->format('Ymd')]['display_total'])) {
                $response->assertSee($breakTimes[$now->copy()->startOfMonth()->addMonth()->format('Ymd')]['display_total']);
            }
            $response->assertSee($workTime['display_total']);
        }
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    #[Test]
    public function userCanAccessShowPage()
    {
        $user = User::factory()->create();
        $now = now();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $now->copy()->format('Y-m-d'),
                    'clock_in' => $now->copy()->subHour()->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
                ]);

        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->subMinutes(35)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->subMinutes(20)->format('Y-m-d H:i:s'),
                ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 3. 「詳細」ボタンを押下する
        $response =  $this->get(route('show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // その日の勤怠詳細画面に遷移する
        $response->assertSee($now->year . '年');
        $response->assertSee($now->month. '月');
        $response->assertSee($now->day. '日');
    }
}
