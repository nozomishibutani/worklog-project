<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginForm;
use App\Enums\Role;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use App\Services\AttendanceCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GetAttendanceDetailAndEditTest extends TestCase
{
    /**
     * 勤怠詳細情報取得・修正機能（管理者）
     */
    use RefreshDatabase;

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    #[Test]
    public function displayDataIsAccurate()
    {
        $user = User::factory()->create();
        $now = now();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->copy()->subHours($user->id)->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
        ]);
        BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
                'clock_in' => $now->copy()->subMinutes(35)->format('Y-m-d H:i:s'),
                'clock_out' => $now->copy()->subMinutes(20)->format('Y-m-d H:i:s'),
        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'currentAttendanceStatus' => $currentAttendanceStatus,
            'note' => $note,
        ]
        = $attendanceCalculatorService->getUserDailyAttendance($attendance->id, null);

        // 詳細画面の内容が選択した情報と一致する
        // 日付け
        $response->assertSee($workDate);
        $this->assertTrue(
            Carbon::parse($workDate['year'] . '-' .$workDate['month'] . '-' .$workDate['day'])->equalTo(Carbon::parse($attendance->work_date))
        );
        // 出勤・退勤
        $response->assertSee($workTimes['clock_in']);
        $response->assertSee($workTimes['clock_out']);
        $this->assertEquals($workTimes['clock_in'], ($attendance->clock_in)->format('H:i'));
        $this->assertEquals($workTimes['clock_out'], ($attendance->clock_out)->format('H:i'));

        // 休憩
        foreach ($breakTimes as $breakTime) {
            $response->assertSee($breakTime['clock_in']);
            $response->assertSee($breakTime['clock_out']);
            $tmp = BreakTime::where([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->subMinutes(35)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->subMinutes(20)->format('Y-m-d H:i:s'),
            ])->first();
            $this->assertEquals($breakTime['clock_in'], ($tmp->clock_in)->format('H:i'));
            $this->assertEquals($breakTime['clock_out'], ($tmp->clock_out)->format('H:i'));
        }
        // 備考
        $response->assertSee($note);
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    #[Test]
    public function isClockInAfterClockOut()
    {
        $user = User::factory()->create();
        $now = now();

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

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'currentAttendanceStatus' => $currentAttendanceStatus,
            'note' => $note,
        ]
        = $attendanceCalculatorService->getUserDailyAttendance($attendance->id, null);

        $breakIn[0] =  null;
        $breakOut[0] =  null;
        foreach ($breakTimes as $breakTime) {
            $breakIn[$breakTime['id']] =  $breakTime['clock_in'];
            $breakOut[$breakTime['id']] =  $breakTime['clock_out'];
        }

        // 3. 出勤時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response =  $this->post(route('admin.update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => $currentAttendanceStatus,
            'year' => $workDate['year'],
            'month' => $workDate['month'],
            'day' => $workDate['day'],
            'work_in' => $now->copy()->addMinutes(65)->format('H:i'),
            'work_out' => $workTimes['clock_out'],
            'break_in' => $breakIn,
            'break_out' => $breakOut,
            'note' => $note,
        ]);

        //「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'work_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    #[Test]
    public function isBreakInAfterClockOut()
    {
        $user = User::factory()->create();
        $now = now();

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

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'currentAttendanceStatus' => $currentAttendanceStatus,
            'note' => $note,
        ]
        = $attendanceCalculatorService->getUserDailyAttendance($attendance->id, null);

        // 3. 休憩開始時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $breakIn[0] =  null;
        $breakOut[0] =  null;
        foreach ($breakTimes as $breakTime) {
            $breakIn[$breakTime['id']] =  $now->copy()->addMinutes(10)->format('H:i');
            $breakOut[$breakTime['id']] = $breakTime['clock_out'];
        }

        $response =  $this->post(route('admin.update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => $currentAttendanceStatus,
            'year' => $workDate['year'],
            'month' => $workDate['month'],
            'day' => $workDate['day'],
            'work_in' => $workTimes['clock_in'],
            'work_out' => $workTimes['clock_out'],
            'break_in' => $breakIn,
            'break_out' => $breakOut,
            'note' => $note,
        ]);

        //「休憩時間が不適切な値です」というバリデーションメッセージが表示される
        foreach ($breakTimes as $breakTime) {
            $response->assertSessionHasErrors([
                'break_in.' . $breakTime['id'] => '休憩時間が不適切な値です'
            ]);
        }
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    #[Test]
    public function isBreakOffAfterClockOut()
    {
        $user = User::factory()->create();
        $now = now();

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

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'currentAttendanceStatus' => $currentAttendanceStatus,
            'note' => $note,
        ]
        = $attendanceCalculatorService->getUserDailyAttendance($attendance->id, null);

        // 3. 休憩終了時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $breakIn[0] =  null;
        $breakOut[0] =  null;
        foreach ($breakTimes as $breakTime) {
            $breakIn[$breakTime['id']] =  $breakTime['clock_in'];
            $breakOut[$breakTime['id']] = $now->copy()->addMinutes(10)->format('H:i');
        }

        $response =  $this->post(route('admin.update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => $currentAttendanceStatus,
            'year' => $workDate['year'],
            'month' => $workDate['month'],
            'day' => $workDate['day'],
            'work_in' => $workTimes['clock_in'],
            'work_out' => $workTimes['clock_out'],
            'break_in' => $breakIn,
            'break_out' => $breakOut,
            'note' => $note,
        ]);

        //「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        foreach ($breakTimes as $breakTime) {
            $response->assertSessionHasErrors([
                        'break_in.' . $breakTime['id'] => '休憩時間もしくは退勤時間が不適切な値です'
                    ]);
        }
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    #[Test]
    public function NoteIsRequired()
    {
        $user = User::factory()->create();
        $now = now();

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

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'currentAttendanceStatus' => $currentAttendanceStatus,
            'note' => $note,
        ]
        = $attendanceCalculatorService->getUserDailyAttendance($attendance->id, null);

        // 3. 備考欄を未入力のまま保存処理をする
        $breakIn[0] =  null;
        $breakOut[0] =  null;
        foreach ($breakTimes as $breakTime) {
            $breakIn[$breakTime['id']] =  $breakTime['clock_in'];
            $breakOut[$breakTime['id']] = $breakTime['clock_out'];
        }

        $response =  $this->post(route('admin.update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => $currentAttendanceStatus,
            'year' => $workDate['year'],
            'month' => $workDate['month'],
            'day' => $workDate['day'],
            'work_in' => $workTimes['clock_in'],
            'work_out' => $workTimes['clock_out'],
            'break_in' => $breakIn,
            'break_out' => $breakOut,
            'note' => '',
        ]);

        // 「備考を記入してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'note' => '備考を記入してください'
        ]);
    }
}
