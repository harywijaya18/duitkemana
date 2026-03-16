<?php

class HealthController extends Controller
{
    private RecurringBillModel $recurringBillModel;

    public function __construct()
    {
        $this->recurringBillModel = $this->model(RecurringBillModel::class);
    }

    /**
     * Recurring scheduler health check for current user.
     * Route: GET /health/recurring
     */
    public function recurring(): void
    {
        require_auth();

        $user = auth_user();
        $month = (int) ($_GET['month'] ?? date('n'));
        $year = (int) ($_GET['year'] ?? date('Y'));

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2099) {
            $this->json([
                'ok' => false,
                'error' => 'Invalid month/year',
            ], 422);
        }

        $snapshot = $this->recurringBillModel->healthSnapshotForMonth((int) $user['id'], $year, $month);

        $defaultsReady = ($snapshot['defaults']['payment_method_id'] > 0) && ($snapshot['defaults']['category_id'] > 0);
        $matchesExpected = $snapshot['active']['count'] === $snapshot['generated']['count'];
        $noDuplicates = $snapshot['duplicates']['count'] === 0;

        $this->json([
            'ok' => $defaultsReady && $matchesExpected && $noDuplicates,
            'checks' => [
                'defaults_ready' => $defaultsReady,
                'active_matches_generated' => $matchesExpected,
                'duplicates_absent' => $noDuplicates,
            ],
            'snapshot' => $snapshot,
        ]);
    }
}
