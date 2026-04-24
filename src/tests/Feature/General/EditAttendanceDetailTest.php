<?php

namespace Tests\Feature\Auth;

use App\Enums\ApprovalStatus;
use App\Enums\LoginForm;
use App\Enums\Role;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceChange;
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
        $startOfMonth = now()->startOfMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $startOfMonth->copy()->format('Y-m-d'),
                    'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'),
                    'clock_out' => $startOfMonth->copy()->addHour(8)->format('Y-m-d H:i:s'),
                ]);

        $breakTime = BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHour(6)->format('Y-m-d H:i:s'),
                    'clock_out' => $startOfMonth->copy()->addHour(7)->format('Y-m-d H:i:s'),
                ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 3. 出勤時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response =  $this->post(route('update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => null,
            'year' => $startOfMonth->copy()->format('Y'),
            'month' => $startOfMonth->copy()->format('n'),
            'day' => $startOfMonth->copy()->format('j'),
            'work_in' => $startOfMonth->copy()->addHour(9)->format('H:i'), // 09:00
            'work_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
            'break_in' => [
                0 => null,
                $breakTime->id => null,
            ],
            'break_out' => [
                0 => null,
                $breakTime->id => null,
            ],
            'note' => '備考欄',
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
        $startOfMonth = now()->startOfMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $startOfMonth->copy()->format('Y-m-d'),
                    'clock_in' => $startOfMonth->copy()->format('H:i'), // 0:00
                    'clock_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
                ]);

        $breakTime = BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHour(6)->format('H:i'), // 06:00
                    'clock_out' => $startOfMonth->copy()->addHour(7)->format('H:i'), // 07:00
                ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 3. 休憩開始時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response =  $this->post(route('update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => null,
            'year' => $startOfMonth->copy()->format('Y'),
            'month' => $startOfMonth->copy()->format('n'),
            'day' => $startOfMonth->copy()->format('j'),
            'work_in' => $startOfMonth->copy()->format('H:i'), // 0:00
            'work_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
            'break_in' => [
                0 => null,
                $breakTime->id => $startOfMonth->copy()->addHour(9)->format('H:i'), // 09:00
            ],
            'break_out' => [
                0 => null,
                $breakTime->id => $startOfMonth->copy()->addHour(10)->format('H:i'), // 10:00
            ],
            'note' => '備考欄',
        ]);

        //「休憩時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
                    'break_in.' . $breakTime->id => '休憩時間が不適切な値です'
                ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    #[Test]
    public function isBreakOffAfterClockOut()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $startOfMonth->copy()->format('Y-m-d'),
                    'clock_in' => $startOfMonth->copy()->format('H:i'), // 0:00
                    'clock_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
                ]);

        $breakTime = BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHour(6)->format('H:i'), // 06:00
                    'clock_out' => $startOfMonth->copy()->addHour(7)->format('H:i'), // 07:00
                ]);


        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 3. 休憩終了時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response =  $this->post(route('update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => null,
            'year' => $startOfMonth->copy()->format('Y'),
            'month' => $startOfMonth->copy()->format('n'),
            'day' => $startOfMonth->copy()->format('j'),
            'work_in' => $startOfMonth->copy()->format('H:i'), // 0:00
            'work_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
            'break_in' => [
                0 => null,
                $breakTime->id => $startOfMonth->copy()->addHour(6)->format('H:i'), // 06:00
            ],
            'break_out' => [
                0 => null,
                $breakTime->id => $startOfMonth->copy()->addHour(9)->format('H:i'), // 09:00
            ],
            'note' => '備考欄',
        ]);

        //「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
                    'break_in.' . $breakTime->id => '休憩時間もしくは退勤時間が不適切な値です'
                ]);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    #[Test]
    public function NoteIsRequired()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $startOfMonth->copy()->format('Y-m-d'),
                    'clock_in' => $startOfMonth->copy()->format('H:i'), // 0:00
                    'clock_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
                ]);

        $breakTime = BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHour(6)->format('H:i'), // 06:00
                    'clock_out' => $startOfMonth->copy()->addHour(7)->format('H:i'), // 07:00
                ]);


        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 3. 備考欄を未入力のまま保存処理する
        $response =  $this->post(route('update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => null,
            'year' => $startOfMonth->copy()->format('Y'),
            'month' => $startOfMonth->copy()->format('n'),
            'day' => $startOfMonth->copy()->format('j'),
            'work_in' => $startOfMonth->copy()->format('H:i'), // 0:00
            'work_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
            'break_in' => [
                0 => null,
                $breakTime->id => $startOfMonth->copy()->addHour(6)->format('H:i'), // 06:00
            ],
            'break_out' => [
                0 => null,
                $breakTime->id => $startOfMonth->copy()->addHour(7)->format('H:i'), // 07:00
            ],
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
        $startOfMonth = now()->startOfMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $startOfMonth->copy()->format('Y-m-d'),
                    'clock_in' => $startOfMonth->copy()->format('H:i'), // 0:00
                    'clock_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
                ]);

        $breakTime = BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHour(6)->format('H:i'), // 06:00
                    'clock_out' => $startOfMonth->copy()->addHour(7)->format('H:i'), // 07:00
                ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠詳細を修正し保存処理する
        $this->post(route('update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => null,
            'year' => $startOfMonth->copy()->format('Y'),
            'month' => $startOfMonth->copy()->format('n'),
            'day' => $startOfMonth->copy()->format('j'),
            'work_in' => $startOfMonth->copy()->format('H:i'), // 0:00
            'work_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
            'break_in' => [
                0 => null,
                $breakTime->id => null,
            ],
            'break_out' => [
                0 => null,
                $breakTime->id => null,
            ],
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
        $response->assertSee($user->name);
        $response->assertSee($startOfMonth->copy()->format('Y年'));
        $response->assertSee($startOfMonth->copy()->format('n月'));
        $response->assertSee($startOfMonth->copy()->format('j日'));
        $response->assertSee($startOfMonth->copy()->format('H:i'));
        $response->assertSee($startOfMonth->copy()->addHour(8)->format('H:i'));
        $response->assertSee('0:00');
        $response->assertSee('備考欄');

        // 申請一覧画面
        $response = $this->actingAs($admin)->get(route('application.index', [
                    'mode' => ApprovalStatus::PENDING->value
        ]));
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            ApprovalStatus::PENDING->label(),
            $user->name,
            $startOfMonth->copy()->format('Y/m/d'),
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
        $startOfMonth = now()->startOfMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        session([
                    'user_id' => $user->id,
                    'login_form' => LoginForm::GENERAL->value,
                ]);
        $this->actingAs($user);

        for ($i = 0; $i < 3; $i++) {
            $date = $startOfMonth->copy()->addDays($i);
            $attendance = Attendance::factory()->create([
                        'user_id' => $user->id,
                        'work_date' => $date->copy()->format('Y-m-d'),
                        'clock_in' => $date->copy()->format('H:i'), // 0:00
                        'clock_out' => $date->copy()->addHour(8)->format('H:i'), // 08:00
                    ]);

            $breakTime = BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'clock_in' => $date->copy()->addHour(6)->format('H:i'), // 06:00
                        'clock_out' => $date->copy()->addHour(7)->format('H:i'), // 07:00
                    ]);

            // 2. 勤怠詳細を修正し保存処理する
            $this->post(route('update'), [
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'current_attendance_status' => null,
                'year' => $startOfMonth->copy()->format('Y'),
                'month' => $startOfMonth->copy()->format('n'),
                'day' => $startOfMonth->copy()->format('j'),
                'work_in' => $startOfMonth->copy()->format('H:i'), // 0:00
                'work_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
                'break_in' => [
                    0 => null,
                    $breakTime->id => null,
                ],
                'break_out' => [
                    0 => null,
                    $breakTime->id => null,
                ],
                'note' => '備考欄',
            ]);
        }

        // 3. 申請一覧画面を確認する
        // 申請一覧に自分の申請が全て表示されている
        $response = $this->actingAs($user)->get(route('application.index', [
            'mode' => ApprovalStatus::PENDING->value
        ]));
        $response->assertStatus(200);
        for ($i = 0; $i < 3; $i++) {
            $date = $startOfMonth->copy()->addDays($i);
            $response->assertSeeInOrder([
                ApprovalStatus::PENDING->label(),
                $user->name,
                $date->copy()->format('Y/m/d'),
                '備考欄',
                now()->copy()->format('Y/m/d'),
            ]);
        }
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    #[Test]
    public function allApprovedApplicantsAreDisplayed()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        $startOfMonth = now()->startOfMonth();
        $attendanceUpdateService = app(AttendanceUpdateService::class);

        // 1. 勤怠情報が登録されたユーザーにログインをする
        session([
                    'user_id' => $user->id,
                    'login_form' => LoginForm::GENERAL->value,
                ]);
        $this->actingAs($user);

        for ($i = 0; $i < 3; $i++) {
            $date = $startOfMonth->copy()->addDays($i);
            $attendance = Attendance::factory()->create([
                        'user_id' => $user->id,
                        'work_date' => $date->copy()->format('Y-m-d'),
                        'clock_in' => $date->copy()->format('H:i'), // 0:00
                        'clock_out' => $date->copy()->addHour(8)->format('H:i'), // 08:00
                    ]);

            $breakTime = BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'clock_in' => $date->copy()->addHour(6)->format('H:i'), // 06:00
                        'clock_out' => $date->copy()->addHour(7)->format('H:i'), // 07:00
                    ]);

            // 2. 勤怠詳細を修正し保存処理する
            $this->post(route('update'), [
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'current_attendance_status' => null,
                'year' => $date->copy()->format('Y'),
                'month' => $date->copy()->format('n'),
                'day' => $date->copy()->format('j'),
                'work_in' => $date->copy()->format('H:i'), // 0:00
                'work_out' => $date->copy()->addHour(8)->format('H:i'), // 08:00
                'break_in' => [
                    0 => null,
                    $breakTime->id => null,
                ],
                'break_out' => [
                    0 => null,
                    $breakTime->id => null,
                ],
                'note' => '備考欄' . $i,
            ]);

            // 承認する
            $attendanceChange = AttendanceChange::where('attendance_id', $attendance->id)->first();
            $attendanceUpdateService->approveAttendance($attendanceChange->id, $admin->id);
        }

        // 3. 申請一覧画面を確認する
        $response = $this->actingAs($user)->get(route('application.index', [
            'mode' => ApprovalStatus::APPROVED->value
        ]));
        $response->assertStatus(200);

        // 4. 管理者が承認した修正申請が全て表示されることを確認
        // 承認済みに管理者が承認した申請が全て表示されている
        for ($i = 0; $i < 3; $i++) {
            $date = $startOfMonth->copy()->addDays($i);
            $response->assertSeeInOrder([
                ApprovalStatus::APPROVED->label(),
                $user->name,
                $date->copy()->format('Y/m/d'),
                '備考欄' . $i,
                now()->copy()->format('Y/m/d'),
            ]);
        }
    }

    /**
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    #[Test]
    public function userCanAccessShowPage()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $startOfMonth->copy()->format('Y-m-d'),
                    'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'), // 0:00
                    'clock_out' => $startOfMonth->copy()->addHour(8)->format('Y-m-d H:i:s'), // 08:00
                ]);

        $breakTime = BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHour(6)->format('Y-m-d H:i:s'), // 06:00
                    'clock_out' => $startOfMonth->copy()->addHour(7)->format('Y-m-d H:i:s'), // 07:00
                ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠詳細を修正し保存処理する
        $this->post(route('update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => null,
            'year' => $startOfMonth->copy()->format('Y'),
            'month' => $startOfMonth->copy()->format('n'),
            'day' => $startOfMonth->copy()->format('j'),
            'work_in' => $startOfMonth->copy()->format('H:i'), // 0:00
            'work_out' => $startOfMonth->copy()->addHour(8)->format('H:i'), // 08:00
            'break_in' => [
                0 => null,
                $breakTime->id => null,
            ],
            'break_out' => [
                0 => null,
                $breakTime->id => null,
            ],
            'note' => '備考欄',
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
            $user->name,
            $startOfMonth->copy()->format('Y') . '年',
            $startOfMonth->copy()->format('n'). '月',
            $startOfMonth->copy()->format('j'). '日',
            $startOfMonth->copy()->format('H:i'),
            $startOfMonth->copy()->addHour(8)->format('H:i'),
            '備考欄',
        ]);
    }
}
