<?php

namespace App\Console\Commands;

use APP\Models\Loan;
use Illuminate\Console\Command;

class SendOverdueLoanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:send-overdue-loan-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        //
        $now = now();

        $overdueLoans = Loan::with('user', 'book')
        ->whereNull('returned_at')
        ->whereNotNUll('due_at')
        ->where('due_at', '<', $now)
        ->get();
    }

    if($overdueLoans->isEmpty()) {
        $this->info('No overdue loans found.');
        return Command::SUCCESS;
    }

    foreach($overdueLoans as $loan) {
        $user = $loan->user;
        $book = $loan->book;

        Log::info("overdue loanreminder", [
            'user_id' => $user->id ?? null,
            'user_email' => $user->email ?? null,
            'book_id' => $book->id ?? null,
            'book_title' => $book->title ?? null,
            'loan_id' => $loan->id,
        ]);

        if($user && $user->email) {
            Mail::raw(
                "Hi {$user->name}, \n\nYour loan for '{$book->title}' is overdue"
                function($message) use($user) {
                    $message->to($user->email)
                    ->subject('Overdue Book Loan Reminder');
                }
            );
        }
    }

    $this->info("Processed {$overdueLoans->count()} overdue loans");

    return Command::SUCCESS;
}
