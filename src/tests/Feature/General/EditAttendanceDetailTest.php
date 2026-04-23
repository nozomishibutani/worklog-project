<?php

namespace Tests\Feature\Auth;

use App\Enums\ApprovalStatus;
use App\Enums\LoginForm;
use App\Enums\Role;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use App\Services\AttendanceCalculatorService;
use App\Services\AttendanceUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class EditAttendanceDetailTest extends TestCase
{
    /**
     * 勤怠詳細情報修正機能（一般ユーザー）
     */
    use RefreshDatabase;

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    #[Test]
    public function isClockInAfterClockOut()
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

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('show', ['id' => $attendance->id]));
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
        $response =  $this->post(route('update'), [
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

        //「出勤時間が不適切な値です」というバリデーションメッセージが表示される
        /*
        |---------------------------------------------------------------------------------------------
        | FN0291
        |---------------------------------------------------------------------------------------------
        |1. 出勤時間が退勤時間より後になっている場合，および退勤時間が出勤時間より前になっている場合に
        |   以下のメッセージを表示
        |
        | ・出勤時間もしくは退勤時間が不適切な値です
        |---------------------------------------------------------------------------------------------
        */
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

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('show', ['id' => $attendance->id]));
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

        $response =  $this->post(route('update'), [
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

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('show', ['id' => $attendance->id]));
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

        $response =  $this->post(route('update'), [
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

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('show', ['id' => $attendance->id]));
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

        $response =  $this->post(route('update'), [
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

    /**
     * 修正申請処理が実行される
     */
    #[Test]
    public function editProgressIsWorking()
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
            $breakIn[$breakTime['id']] =  $now->copy()->subMinutes(60)->format('H:i');
            $breakOut[$breakTime['id']] = $now->copy()->subMinutes(45)->format('H:i');
        }

        // 2. 勤怠詳細を修正し保存処理をする
        $this->post(route('update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => $currentAttendanceStatus,
            'year' => $workDate['year'],
            'month' => $workDate['month'],
            'day' => $workDate['day'],
            'work_in' => $now->copy()->subMinutes(75)->format('H:i'),
            'work_out' => $now->copy()->subMinutes(15)->format('H:i'),
            'break_in' => $breakIn,
            'break_out' => $breakOut,
            'note' => '備考欄',
        ]);
        $attendanceChange = $attendance->attendanceChanges()->first();

        // 3. 管理者ユーザーで承認画面と申請一覧画面を確認する
        // 修正申請が実行され、管理者の承認画面と申請一覧画面に表示される
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        // 承認画面
        $response = $this->actingAs($admin)->get(route('admin.approval.show', [
            'attendance_correct_request_id' => $attendanceChange->id
        ]));
        $response->assertStatus(200);
        $response->assertSee($workTimes['name']);
        $response->assertSee($workDate['year']);
        $response->assertSee($workDate['month']);
        $response->assertSee($workDate['day']);
        $response->assertSee($now->copy()->subMinutes(75)->format('H:i'));
        $response->assertSee($now->copy()->subMinutes(15)->format('H:i'));
        $response->assertSee($now->copy()->subMinutes(60)->format('H:i'));
        $response->assertSee($now->copy()->subMinutes(45)->format('H:i'));
        $response->assertSee('備考欄');

        // 申請一覧画面
        $response = $this->actingAs($admin)->get(route('application.index', [
                    'mode' => ApprovalStatus::PENDING->value
        ]));
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            ApprovalStatus::PENDING->label(),
            $workTimes['name'],
            Carbon::parse($workDate['year']. '/' . $workDate['month'] . '/' . $workDate['day'])->format('Y/m/d'),
            '備考欄',
            ($attendanceChange->applied_at)->format('Y/m/d'),
        ]);
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    #[Test]
    public function allApplicantsAreDisplayedOnPendingList()
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
            $breakIn[$breakTime['id']] =  $now->copy()->subMinutes(60)->format('H:i');
            $breakOut[$breakTime['id']] = $now->copy()->subMinutes(45)->format('H:i');
        }

        // 2. 勤怠詳細を修正し保存処理をする
        $this->post(route('update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => $currentAttendanceStatus,
            'year' => $workDate['year'],
            'month' => $workDate['month'],
            'day' => $workDate['day'],
            'work_in' => $now->copy()->subMinutes(75)->format('H:i'),
            'work_out' => $now->copy()->subMinutes(15)->format('H:i'),
            'break_in' => $breakIn,
            'break_out' => $breakOut,
            'note' => '備考欄備考欄',
        ]);
        $attendanceChange = $attendance->attendanceChanges()->first();

        // 3. 申請一覧画面を確認する
        // 申請一覧に自分の申請が全て表示されている
        $response = $this->actingAs($user)->get(route('application.index', [
            'mode' => ApprovalStatus::PENDING->value
        ]));
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            ApprovalStatus::PENDING->label(),
            $workTimes['name'],
            Carbon::parse($workDate['year']. '/' . $workDate['month'] . '/' . $workDate['day'])->format('Y/m/d'),
            '備考欄備考欄',
            ($attendanceChange->applied_at)->format('Y/m/d'),
        ]);
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    #[Test]
    public function allApprovedApplicantsAreDisplayed()
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
            $breakIn[$breakTime['id']] =  $now->copy()->subMinutes(60)->format('H:i');
            $breakOut[$breakTime['id']] = $now->copy()->subMinutes(45)->format('H:i');
        }

        // 2. 勤怠詳細を修正し保存処理をする
        $this->post(route('update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => $currentAttendanceStatus,
            'year' => $workDate['year'],
            'month' => $workDate['month'],
            'day' => $workDate['day'],
            'work_in' => $now->copy()->subMinutes(75)->format('H:i'),
            'work_out' => $now->copy()->subMinutes(15)->format('H:i'),
            'break_in' => $breakIn,
            'break_out' => $breakOut,
            'note' => '備考欄備考欄備考欄',
        ]);
        $attendanceChange = $attendance->attendanceChanges()->first();

        // 承認する
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $attendanceUpdateService = app(AttendanceUpdateService::class);
        $attendanceUpdateService->approveAttendance($attendanceChange->id, $admin->id);

        // 3. 申請一覧画面を開く
        $response = $this->actingAs($user)->get(route('application.index', [
            'mode' => ApprovalStatus::APPROVED->value
        ]));
        $response->assertStatus(200);

        // 4. 管理者が承認した修正申請が全て表示されていることを確認
        // 承認済みに管理者が承認した申請が全て表示されている
        $response->assertSeeInOrder([
            ApprovalStatus::APPROVED->label(),
            $workTimes['name'],
            Carbon::parse($workDate['year']. '/' . $workDate['month'] . '/' . $workDate['day'])->format('Y/m/d'),
            '備考欄備考欄備考欄',
            ($attendanceChange->applied_at)->format('Y/m/d'),
        ]);
    }

    /**
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
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
            $breakIn[$breakTime['id']] =  $now->copy()->subMinutes(60)->format('H:i');
            $breakOut[$breakTime['id']] = $now->copy()->subMinutes(45)->format('H:i');
        }

        // 2. 勤怠詳細を修正し保存処理をする
        $this->post(route('update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => $currentAttendanceStatus,
            'year' => $workDate['year'],
            'month' => $workDate['month'],
            'day' => $workDate['day'],
            'work_in' => $now->copy()->subMinutes(75)->format('H:i'),
            'work_out' => $now->copy()->subMinutes(15)->format('H:i'),
            'break_in' => $breakIn,
            'break_out' => $breakOut,
            'note' => '備考欄備考欄備考欄備考欄',
        ]);

        // 3. 申請一覧画面を開く
        $response = $this->actingAs($user)->get(route('application.index', [
            'mode' => ApprovalStatus::PENDING->value
        ]));
        $response->assertStatus(200);

        // 4.「詳細ボタン」を押す
        // 勤怠詳細画面に遷移する
        $response =  $this->get(route('show', ['id' => $attendance->id]));
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            $workTimes['name'],
            $workDate['year'] . '年',
            $workDate['month']. '月',
            $workDate['day']. '日',
        ]);
    }
}
