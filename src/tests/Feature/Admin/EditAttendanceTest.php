<?php

namespace Tests\Feature\Auth;

use App\Enums\ApprovalStatus;
use App\Enums\LoginForm;
use App\Enums\Role;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceChange;
use App\Models\BreakTimeChange;
use Carbon\Carbon;
use App\Services\AttendanceUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class EditAttendanceTest extends TestCase
{
    /**
     * 勤怠情報修正機能（管理者）
     */
    use RefreshDatabase;

    /**
     * 承認待ちの修正申請が全て表示されている
     */
    #[Test]
    public function adminCanGetAllUserDetail()
    {
        $users = User::factory(3)->create();
        $startOfMonth = now()->startOfMonth();
        foreach ($users as $user) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $startOfMonth->copy()->format('Y-m-d'),
                'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'), // 0:00
                'clock_out' => $startOfMonth->copy()->addHours()->format('Y-m-d H:i:s'), // 8:00
            ]);
            BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'clock_in' => $startOfMonth->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
                        'clock_out' => $startOfMonth->copy()->addHours(7)->format('Y-m-d H:i:s'), // 7:00
            ]);
            $attendanceChange = AttendanceChange::factory()->create([
                            'attendance_id' => $attendance->id,
                            'user_id' => $user->id,
                            'work_date' => $attendance->work_date,
                            'clock_in' => $attendance->clock_in,
                            'clock_out' => $startOfMonth->copy()->addHours(4)->format('Y-m-d H:i:s'), // 4:00
                            'note' => '承認待ち' . $user->name,
                            'applied_by' => $user->id,
                        ]);
        }

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 修正申請一覧ページを開き、承認待ちタブを開く
        // 全ユーザーの未承認の修正申請が表示される
        $response = $this->actingAs($admin)->get(route('application.index', [
                    'mode' => ApprovalStatus::PENDING->value
                ]));
        $response->assertStatus(200);
        $count = 0;
        foreach ($users as $user) {
            $count++;
            $attendanceChange = AttendanceChange::where('user_id', $user->id)->first();
            $response->assertSeeInOrder([
                        ApprovalStatus::PENDING->label(),
                        $user->name,
                        Carbon::parse($attendanceChange->work_date)->format('Y/m/d'),
                        '承認待ち' . $user->name,
                        ($attendanceChange->applied_at)->format('Y/m/d'),
                    ]);
        }
        $this->assertEquals(count($users), $count);
    }

    /**
     * 承認済みの修正申請が全て表示されている
     */
    #[Test]
    public function allApprovedApplicantsAreDisplayed()
    {
        $attendanceUpdateService = app(AttendanceUpdateService::class);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        $users = User::factory(3)->create();
        $startOfMonth = now()->startOfMonth();
        foreach ($users as $user) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $startOfMonth->copy()->format('Y-m-d'),
                'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'), // 0:00
                'clock_out' => $startOfMonth->copy()->addHours()->format('Y-m-d H:i:s'), // 8:00
            ]);
            BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'clock_in' => $startOfMonth->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
                        'clock_out' => $startOfMonth->copy()->addHours(7)->format('Y-m-d H:i:s'), // 7:00
            ]);
            $attendanceChange = AttendanceChange::factory()->create([
                            'attendance_id' => $attendance->id,
                            'user_id' => $user->id,
                            'work_date' => $attendance->work_date,
                            'clock_in' => $attendance->clock_in,
                            'clock_out' => $attendance->clock_out,
                            'note' => '承認済み' . $user->name,
                            'applied_by' => $user->id,
                        ]);
            BreakTimeChange::factory()->create([
                        'attendance_change_id' => $attendanceChange->id,
                        'clock_in' => $startOfMonth->copy()->addHours(6.5)->format('Y-m-d H:i:s'), // 6:30
                        'clock_out' => $startOfMonth->copy()->addHours(7.5)->format('Y-m-d H:i:s'), // 7:30
                        ]);
            // 承認する
            $attendanceUpdateService->approveAttendance($attendanceChange->id, $admin->id);
        }

        // 2. 修正申請一覧ページを開き、承認済みのタブを開く
        // 全ユーザーの承認済みの修正申請が表示される
        $response = $this->actingAs($admin)->get(route('application.index', [
                    'mode' => ApprovalStatus::APPROVED->value
                ]));
        $response->assertStatus(200);

        $count = 0;
        foreach ($users as $user) {
            $count++;
            $attendanceChange = AttendanceChange::where('user_id', $user->id)->first();
            $response->assertSeeInOrder([
                        ApprovalStatus::APPROVED->label(),
                        $user->name,
                        Carbon::parse($attendanceChange->work_date)->format('Y/m/d'),
                        '承認済み' . $user->name,
                        ($attendanceChange->applied_at)->format('Y/m/d'),
                    ]);
        }
        $this->assertEquals(count($users), $count);
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     */
    #[Test]
    public function ApplicantDetailIsDisplayedAccurately()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $startOfMonth->copy()->format('Y-m-d'),
            'clock_in' => $startOfMonth->copy()->addHour()->format('Y-m-d H:i:s'), // 1:00
            'clock_out' => null,
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHours(3)->format('Y-m-d H:i:s'), // 3:00
                    'clock_out' => null,
        ]);
        $attendanceChange = AttendanceChange::factory()->create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'work_date' => $attendance->work_date,
                        'clock_in' => $startOfMonth->copy()->addHour()->format('Y-m-d H:i:s'), // 1:00
                        'clock_out' => $startOfMonth->copy()->addHours(9.5)->format('Y-m-d H:i:s'), // 9:30
                        'note' => '備考欄' . $user->name,
                        'applied_by' => $user->id,
                    ]);
        BreakTimeChange::factory()->create([
                        'attendance_change_id' => $attendanceChange->id,
                        'clock_in' => $startOfMonth->copy()->addHours(6.5)->format('Y-m-d H:i:s'), // 6:30
                        'clock_out' => $startOfMonth->copy()->addHours(7.5)->format('Y-m-d H:i:s'), // 7:30
                    ]);
        BreakTimeChange::factory()->create([
                        'attendance_change_id' => $attendanceChange->id,
                        'clock_in' => $startOfMonth->copy()->addHours(8.5)->format('Y-m-d H:i:s'), // 8:30
                       'clock_out' => $startOfMonth->copy()->addHours(9)->format('Y-m-d H:i:s'), // 9:00
                    ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 修正申請の詳細画面を開く
        // 申請内容が正しく表示されている
        $response = $this->actingAs($admin)->get(route('admin.approval.show', [
                    'attendance_correct_request_id' => $attendanceChange->id
                ]));
        $response->assertStatus(200);

        $response->assertSeeInOrder([
                    $user->name,
                    $startOfMonth->copy()->format('Y年'),
                    $startOfMonth->copy()->format('n月'),
                    $startOfMonth->copy()->format('j日'),
                    '01:00', // 出勤
                    '09:30', // 退勤
                    '06:30', // 休憩入
                    '07:30', // 休憩戻
                    '08:30', // 休憩入②
                    '09:00', // 休憩戻②
                    '備考欄' . $user->name,
                ]);
    }

    /**
     * 修正申請の承認処理が正しく行われる
     */
    #[Test]
    public function approvalProcessingIsWorking()
    {
        $user = User::factory()->create();

        $startOfMonth = now()->startOfMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $startOfMonth->copy()->format('Y-m-d'),
            'clock_in' => $startOfMonth->copy()->addHour()->format('Y-m-d H:i:s'), // 1:00
            'clock_out' => null,
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHours(3)->format('Y-m-d H:i:s'), // 3:00
                    'clock_out' => null,
        ]);
        $attendanceChange = AttendanceChange::factory()->create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'work_date' => $attendance->work_date,
                        'clock_in' => $startOfMonth->copy()->addHour()->format('Y-m-d H:i:s'), // 1:00
                        'clock_out' => $startOfMonth->copy()->addHours(9.5)->format('Y-m-d H:i:s'), // 9:30
                        'note' => '備考欄' . $user->name,
                        'applied_by' => $user->id,
                    ]);
        BreakTimeChange::factory()->create([
                                'attendance_change_id' => $attendanceChange->id,
                                'clock_in' => $startOfMonth->copy()->addHours(6.5)->format('Y-m-d H:i:s'), // 6:30
                                'clock_out' => $startOfMonth->copy()->addHours(7.5)->format('Y-m-d H:i:s'), // 7:30
                            ]);
        BreakTimeChange::factory()->create([
                                'attendance_change_id' => $attendanceChange->id,
                                'clock_in' => $startOfMonth->copy()->addHours(8.5)->format('Y-m-d H:i:s'), // 8:30
                                'clock_out' => $startOfMonth->copy()->addHours(9)->format('Y-m-d H:i:s'), // 9:00
                            ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 修正申請の詳細画面で「承認」ボタンを押す
        // 修正申請が承認され、勤怠情報が更新される
        $response = $this->actingAs($admin)->post(route('admin.approve', [
                    'attendance_change_id' => $attendanceChange->id,
                ]));

        // 承認待ちには存在しない
        $response = $this->actingAs($admin)->get(route('application.index', [
                    'mode' => ApprovalStatus::PENDING->value
                ]));
        $response->assertStatus(200);
        $response->assertDontSee($user->name);

        // 承認済みに存在する
        $response = $this->actingAs($admin)->get(route('application.index', [
                        'mode' => ApprovalStatus::APPROVED->value
                    ]));
        $response->assertStatus(200);
        $response->assertSeeInOrder([
                    ApprovalStatus::APPROVED->label(),
                    $user->name,
                    Carbon::parse($attendanceChange->work_date)->format('Y/m/d'),
                    '備考欄' . $user->name,
                    ($attendanceChange->applied_at)->format('Y/m/d'),
                ]);
        // 修正申請詳細画面で「承認済み」表示されている
        $response = $this->actingAs($admin)->get(route('admin.approval.show', [
                            'attendance_correct_request_id' => $attendanceChange->id
                        ]));
        $response->assertStatus(200);
        $response->assertSeeInOrder([
                            $user->name,
                            $startOfMonth->copy()->format('Y年'),
                            $startOfMonth->copy()->format('n月'),
                            $startOfMonth->copy()->format('j日'),
                            '01:00', // 出勤
                            '09:30', // 退勤
                            '06:30', // 休憩入
                            '07:30', // 休憩戻
                            '08:30', // 休憩入②
                            '09:00', // 休憩戻②
                            '備考欄' . $user->name,
                        ]);
        $response->assertSee('承認済み</button>', false);
    }
}
