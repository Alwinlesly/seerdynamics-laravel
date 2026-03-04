<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class EmailService
{
    /**
     * Send ticket notification email - equivalent to CI's mail_me()
     * 
     * @param int $taskId
     * @param string $action - NTKT, ASGNCONSLT, TKTCOMPL, TKTCLOSE, ESTIMATE, ESTAPPRV, NMSG
     * @param array $data
     */
    public static function sendTicketEmail($taskId, $action, $data = [])
    {
        try {
            $to = '';

            // Get customer admin emails
            $mails = self::getTaskMailCadmin($taskId);
            if (!empty($mails)) {
                $to = implode(',', array_column($mails, 'email'));
            }

            // Get customer user/consultant emails
            $mails2 = self::getTaskMailCuserConsultant($taskId);
            if (!empty($mails2)) {
                $emails2 = implode(',', array_column($mails2, 'email'));
                $to = $to ? $to . ',' . $emails2 : $emails2;
            }

            // Get task and project details
            $task_det = DB::table('tasks')->where('id', $taskId)->first();
            if (!$task_det) {
                Log::error("EmailService: Task not found - ID: {$taskId}");
                return;
            }

            $project_det = DB::table('projects')->where('id', $task_det->project_id)->first();
            $createdByUser = DB::table('users')->where('id', $task_det->created_by)->first();

            $data['task_id'] = $taskId;
            $data['SupportDetails'] = '';
            $data['title'] = $task_det->title ?? '';
            $data['completed_date'] = $task_det->completed_date ? date('d-m-Y H:i:s', strtotime($task_det->completed_date)) : '';
            $data['closed_date'] = $task_det->closed_date ? date('d-m-Y H:i:s', strtotime($task_det->closed_date)) : '';
            $data['ProjectID'] = $project_det->project_id ?? '';
            $data['CreatedBy'] = $createdByUser ? ($createdByUser->first_name . ' ' . $createdByUser->last_name) : '';
            $data['SupportMessages'] = '';
            $data['IsEtimate'] = ' - Not Estimated';
            $data['EstimatedDays'] = 'Not Estimated';
            $data['EstimatedHours'] = 'Not Estimated';

            // Get estimate details
            $estimate_det = DB::table('task_estimate')->where('task_id', $taskId)->first();
            if ($estimate_det) {
                $data['IsEtimate'] = ' - Estimate Done';
                $data['EstimatedDays'] = $estimate_det->estimate_days ?? '';
                $data['EstimatedHours'] = $estimate_det->estimate_hours ?? '';
                $data['ApprovedOn'] = $estimate_det->estimate_approvedon ?? '';
                if (!is_null($estimate_det->estimate_approvedby) && $estimate_det->estimate_approvedby > 0) {
                    $approvedByUser = DB::table('users')->where('id', $estimate_det->estimate_approvedby)->first();
                    $data['ApprovedBy'] = $approvedByUser ? ($approvedByUser->first_name . ' ' . $approvedByUser->last_name) : '';
                } else {
                    $data['ApprovedBy'] = '';
                }
            }

            // Get assigned consultant
            $data['IsAssigned'] = ' - Not Assigned Consultant';
            $data['AssignedTo'] = '';
            $assigned_users = DB::table('task_users')
                ->join('users', 'users.id', '=', 'task_users.user_id')
                ->join('users_groups', 'users_groups.user_id', '=', 'users.id')
                ->where('task_users.task_id', $taskId)
                ->select('users.id', 'users.first_name', 'users.last_name', 'users_groups.group_id')
                ->get();

            foreach ($assigned_users as $auser) {
                if ($auser->group_id == 2) { // consultant group
                    $data['IsAssigned'] = ' - Assigned Consultant';
                    $data['AssignedTo'] = $auser->first_name . ' ' . $auser->last_name;
                }
            }

            // Build subject
            $subject = '#' . str_pad($taskId, 5, '0', STR_PAD_LEFT) . ' - ' . ($project_det->project_id ?? '') . ' - ' . ($task_det->title ?? '');

            // Select template based on action
            $template = '';
            switch ($action) {
                case 'NTKT':
                    $template = 'emails.support-created';
                    break;
                case 'ASGNCONSLT':
                    $template = 'emails.support-in-process';
                    break;
                case 'TKTCOMPL':
                    $template = 'emails.support-completed';
                    break;
                case 'TKTCLOSE':
                    $template = 'emails.support-closed';
                    break;
                case 'ESTIMATE':
                    $template = 'emails.support-estimate-approval';
                    break;
                case 'ESTAPPRV':
                    $subject = 'Estimate Approved: #' . str_pad($taskId, 5, '0', STR_PAD_LEFT) . ' - ' . ($project_det->project_id ?? '') . ' - ' . ($task_det->title ?? '');
                    $template = 'emails.support-estimate-approved';
                    break;
                case 'NMSG':
                    $template = 'emails.support-message-added';
                    break;
                default:
                    return;
            }

            // Add admin email
            $admin_email = !empty(smtp_username()) ? smtp_username() : 'support@seerdynamics.com';
            if ($to == '') {
                $to = $admin_email;
            } else {
                $to = $to . ',' . $admin_email;
            }

            // Add additional mails from the task
            if (!empty($task_det->additional_mail)) {
                $to = $to . ',' . $task_det->additional_mail;
            }

            // Clean up recipients - remove empty, duplicates
            $recipients = array_filter(array_unique(array_map('trim', explode(',', $to))));

            if (empty($recipients)) {
                Log::warning("EmailService: No recipients for task email - Task ID: {$taskId}, Action: {$action}");
                return;
            }

            // Render email body
            $body = View::make($template, $data)->render();

            // Send email
            Mail::send([], [], function ($message) use ($recipients, $subject, $body, $admin_email) {
                $message->from($admin_email, company_name())
                    ->to($recipients)
                    ->subject($subject)
                    ->html($body);
            });

            Log::info("EmailService: Ticket email sent - Task ID: {$taskId}, Action: {$action}");

        } catch (\Exception $e) {
            Log::error("EmailService: Failed to send ticket email - Task ID: {$taskId}, Action: {$action}, Error: " . $e->getMessage());
        }
    }

    /**
     * Send welcome email to new user
     */
    public static function sendWelcomeEmail($email, $userData = [])
    {
        try {
            $body = View::make('emails.welcome', $userData)->render();

            Mail::send([], [], function ($message) use ($email, $body) {
                $fromEmail = !empty(smtp_username()) ? smtp_username() : 'support@seerdynamics.com';
                $message->from($fromEmail, company_name())
                    ->to($email)
                    ->subject('Welcome to the ' . company_name())
                    ->html($body);
            });

            Log::info("EmailService: Welcome email sent to {$email}");
        } catch (\Exception $e) {
            Log::error("EmailService: Failed to send welcome email to {$email} - " . $e->getMessage());
        }
    }

    /**
     * Send activation/confirmation email to new user
     */
    public static function sendActivationEmail($email, $userId, $activationCode)
    {
        try {
            $body = "<html>
                <body>
                    <p>Welcome to the " . company_name() . ",  Please confirm your email to activate your account.</p>
                    <p><a href='" . url('auth/activate/' . $userId . '/' . $activationCode) . "'>Click to Confirm</a></p>
                    <p>OR Click Here: <a href='" . url('auth/activate/' . $userId . '/' . $activationCode) . "'>" . url('auth/activate/' . $userId . '/' . $activationCode) . "</a></p>
                </body>
            </html>";

            Mail::send([], [], function ($message) use ($email, $body) {
                $fromEmail = !empty(smtp_username()) ? smtp_username() : 'support@seerdynamics.com';
                $message->from($fromEmail, company_name())
                    ->to($email)
                    ->subject('Confirm your email - ' . company_name())
                    ->html($body);
            });

            Log::info("EmailService: Activation email sent to {$email}");
        } catch (\Exception $e) {
            Log::error("EmailService: Failed to send activation email to {$email} - " . $e->getMessage());
        }
    }

    /**
     * Send SMTP test email
     */
    public static function sendTestEmail($toEmail)
    {
        try {
            $body = "<html>
                <body>
                    <p>SMTP is perfectly configured.</p>
                    <p>Go To your workspace <a href='" . url('/') . "'>Click Here</a></p>
                </body>
            </html>";

            Mail::send([], [], function ($message) use ($toEmail, $body) {
                $fromEmail = !empty(smtp_username()) ? smtp_username() : 'support@seerdynamics.com';
                $message->from($fromEmail, company_name())
                    ->to($toEmail)
                    ->subject('Testing SMTP')
                    ->html($body);
            });

            return true;
        } catch (\Exception $e) {
            Log::error("EmailService: SMTP test failed - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get customer admin emails for a task (equivalent to CI's get_task_mail_cadmin)
     */
    private static function getTaskMailCadmin($taskId)
    {
        return DB::table('tasks as t')
            ->join('projects as p', 'p.id', '=', 't.project_id')
            ->join('users as u', 'u.id', '=', 'p.customer_id')
            ->where('t.id', $taskId)
            ->whereNotNull('u.email')
            ->where('u.email', '!=', '')
            ->select('u.email')
            ->get()
            ->toArray();
    }

    /**
     * Get customer user/consultant emails for a task (equivalent to CI's get_task_mail_cuser_consultant)
     */
    private static function getTaskMailCuserConsultant($taskId)
    {
        return DB::table('task_users as tu')
            ->join('users as u', 'u.id', '=', 'tu.user_id')
            ->where('tu.task_id', $taskId)
            ->whereNotNull('u.email')
            ->where('u.email', '!=', '')
            ->select('u.email')
            ->get()
            ->toArray();
    }
}
