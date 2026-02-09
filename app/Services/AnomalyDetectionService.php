<?php

namespace App\Services;

/**
 * Service สำหรับตรวจจับความผิดปกติของผลเลือกตั้ง
 * ตรวจสอบข้อมูลจาก ECT Report 69
 */
class AnomalyDetectionService
{
    /**
     * ระดับความรุนแรงของความผิดปกติ
     */
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_INFO = 'info';

    /**
     * ค่า threshold สำหรับตรวจจับ
     */
    protected array $thresholds = [
        'max_turnout_percent' => 95.0,       // ผู้มาใช้สิทธิ > 95% ถือว่าผิดปกติ
        'min_turnout_percent' => 30.0,       // ผู้มาใช้สิทธิ < 30% ถือว่าผิดปกติ
        'max_bad_ballot_percent' => 10.0,    // บัตรเสีย > 10% ถือว่าผิดปกติ
        'max_no_vote_percent' => 10.0,       // ไม่ประสงค์ลงคะแนน > 10% ถือว่าผิดปกติ
        'max_winner_percent' => 90.0,        // ผู้ชนะได้ > 90% ถือว่าผิดปกติ
        'zero_votes_threshold' => 0,         // ผู้สมัครได้ 0 คะแนน
    ];

    /**
     * วิเคราะห์ข้อมูลเขตเลือกตั้งหนึ่งเขต
     */
    public function analyzeConstituency(array $constituency, string $provinceName): array
    {
        $anomalies = [];

        $eligible = $constituency['eligible_voters'] ?? 0;
        $totalVoters = $constituency['total_voters'] ?? 0;
        $goodBallots = $constituency['good_ballots'] ?? 0;
        $badBallots = $constituency['bad_ballots'] ?? 0;
        $noVote = $constituency['no_vote'] ?? 0;
        $candidates = $constituency['candidates'] ?? [];
        $constNum = $constituency['number'] ?? 0;

        $location = "{$provinceName} เขต {$constNum}";

        // 1. ตรวจสอบ: ผู้มาใช้สิทธิ > ผู้มีสิทธิ (เป็นไปไม่ได้)
        if ($totalVoters > $eligible && $eligible > 0) {
            $anomalies[] = [
                'type' => 'voters_exceed_eligible',
                'severity' => self::SEVERITY_CRITICAL,
                'location' => $location,
                'province' => $provinceName,
                'constituency' => $constNum,
                'message' => "ผู้มาใช้สิทธิ ({$this->fmt($totalVoters)}) มากกว่าผู้มีสิทธิเลือกตั้ง ({$this->fmt($eligible)})",
                'expected' => $eligible,
                'actual' => $totalVoters,
                'difference' => $totalVoters - $eligible,
            ];
        }

        // 2. ตรวจสอบ: บัตรดี + บัตรเสีย ≠ ผู้มาใช้สิทธิ
        $ballotSum = $goodBallots + $badBallots;

        if ($ballotSum !== $totalVoters && $totalVoters > 0) {
            $severity = abs($ballotSum - $totalVoters) > ($totalVoters * 0.01)
                ? self::SEVERITY_CRITICAL
                : self::SEVERITY_WARNING;

            $anomalies[] = [
                'type' => 'ballot_sum_mismatch',
                'severity' => $severity,
                'location' => $location,
                'province' => $provinceName,
                'constituency' => $constNum,
                'message' => "บัตรดี ({$this->fmt($goodBallots)}) + บัตรเสีย ({$this->fmt($badBallots)}) = {$this->fmt($ballotSum)} ≠ ผู้มาใช้สิทธิ ({$this->fmt($totalVoters)})",
                'expected' => $totalVoters,
                'actual' => $ballotSum,
                'difference' => $ballotSum - $totalVoters,
            ];
        }

        // 3. ตรวจสอบ: ผลรวมคะแนนผู้สมัคร + ไม่ประสงค์ลงคะแนน ≠ บัตรดี
        $candidateVoteSum = array_sum(array_column($candidates, 'votes'));
        $voteTotal = $candidateVoteSum + $noVote;

        if ($voteTotal !== $goodBallots && $goodBallots > 0) {
            $severity = abs($voteTotal - $goodBallots) > ($goodBallots * 0.01)
                ? self::SEVERITY_CRITICAL
                : self::SEVERITY_WARNING;

            $anomalies[] = [
                'type' => 'vote_sum_mismatch',
                'severity' => $severity,
                'location' => $location,
                'province' => $provinceName,
                'constituency' => $constNum,
                'message' => "คะแนนผู้สมัครรวม ({$this->fmt($candidateVoteSum)}) + ไม่ประสงค์ ({$this->fmt($noVote)}) = {$this->fmt($voteTotal)} ≠ บัตรดี ({$this->fmt($goodBallots)})",
                'expected' => $goodBallots,
                'actual' => $voteTotal,
                'difference' => $voteTotal - $goodBallots,
            ];
        }

        // 4. ตรวจสอบ: คะแนนผู้ชนะ > บัตรดี (เป็นไปไม่ได้)
        $winner = collect($candidates)->sortByDesc('votes')->first();

        if ($winner && $winner['votes'] > $goodBallots && $goodBallots > 0) {
            $anomalies[] = [
                'type' => 'winner_exceeds_good_ballots',
                'severity' => self::SEVERITY_CRITICAL,
                'location' => $location,
                'province' => $provinceName,
                'constituency' => $constNum,
                'message' => "คะแนนผู้ชนะ {$winner['name']} ({$this->fmt($winner['votes'])}) มากกว่าบัตรดี ({$this->fmt($goodBallots)})",
                'expected' => $goodBallots,
                'actual' => $winner['votes'],
                'candidate' => $winner['name'],
                'party' => $winner['party'] ?? '',
            ];
        }

        // 5. ตรวจสอบ: อัตราการมาใช้สิทธิผิดปกติ
        if ($eligible > 0) {
            $turnoutPercent = ($totalVoters / $eligible) * 100;

            if ($turnoutPercent > $this->thresholds['max_turnout_percent']) {
                $anomalies[] = [
                    'type' => 'abnormal_high_turnout',
                    'severity' => $turnoutPercent > 100 ? self::SEVERITY_CRITICAL : self::SEVERITY_WARNING,
                    'location' => $location,
                    'province' => $provinceName,
                    'constituency' => $constNum,
                    'message' => 'อัตราผู้มาใช้สิทธิสูงผิดปกติ ' . number_format($turnoutPercent, 1) . '%',
                    'value' => round($turnoutPercent, 2),
                    'threshold' => $this->thresholds['max_turnout_percent'],
                ];
            }

            if ($turnoutPercent < $this->thresholds['min_turnout_percent'] && $turnoutPercent > 0) {
                $anomalies[] = [
                    'type' => 'abnormal_low_turnout',
                    'severity' => self::SEVERITY_WARNING,
                    'location' => $location,
                    'province' => $provinceName,
                    'constituency' => $constNum,
                    'message' => 'อัตราผู้มาใช้สิทธิต่ำผิดปกติ ' . number_format($turnoutPercent, 1) . '%',
                    'value' => round($turnoutPercent, 2),
                    'threshold' => $this->thresholds['min_turnout_percent'],
                ];
            }
        }

        // 6. ตรวจสอบ: อัตราบัตรเสียผิดปกติ
        if ($totalVoters > 0) {
            $badBallotPercent = ($badBallots / $totalVoters) * 100;

            if ($badBallotPercent > $this->thresholds['max_bad_ballot_percent']) {
                $anomalies[] = [
                    'type' => 'abnormal_bad_ballot_rate',
                    'severity' => self::SEVERITY_WARNING,
                    'location' => $location,
                    'province' => $provinceName,
                    'constituency' => $constNum,
                    'message' => 'อัตราบัตรเสียสูงผิดปกติ ' . number_format($badBallotPercent, 1) . '% (' . $this->fmt($badBallots) . " จาก {$this->fmt($totalVoters)})",
                    'value' => round($badBallotPercent, 2),
                    'threshold' => $this->thresholds['max_bad_ballot_percent'],
                ];
            }
        }

        // 7. ตรวจสอบ: ผู้สมัครได้ 0 คะแนน (น่าสงสัย)
        foreach ($candidates as $candidate) {
            if ($candidate['votes'] === 0 && $totalVoters > 100) {
                $anomalies[] = [
                    'type' => 'zero_votes_candidate',
                    'severity' => self::SEVERITY_INFO,
                    'location' => $location,
                    'province' => $provinceName,
                    'constituency' => $constNum,
                    'message' => "ผู้สมัคร {$candidate['name']} ({$candidate['party']}) ได้ 0 คะแนน ในเขตที่มีผู้มาใช้สิทธิ {$this->fmt($totalVoters)} คน",
                    'candidate' => $candidate['name'],
                    'party' => $candidate['party'] ?? '',
                ];
            }
        }

        // 8. ตรวจสอบ: ผู้ชนะได้คะแนนสูงผิดปกติ
        if ($winner && $goodBallots > 0) {
            $winnerPercent = ($winner['votes'] / $goodBallots) * 100;

            if ($winnerPercent > $this->thresholds['max_winner_percent']) {
                $anomalies[] = [
                    'type' => 'abnormal_winner_percentage',
                    'severity' => self::SEVERITY_WARNING,
                    'location' => $location,
                    'province' => $provinceName,
                    'constituency' => $constNum,
                    'message' => "ผู้ชนะ {$winner['name']} ได้คะแนนสูงผิดปกติ " . number_format($winnerPercent, 1) . '% ของบัตรดี',
                    'value' => round($winnerPercent, 2),
                    'candidate' => $winner['name'],
                    'party' => $winner['party'] ?? '',
                ];
            }
        }

        return $anomalies;
    }

    /**
     * วิเคราะห์ข้อมูลทั้งจังหวัด
     */
    public function analyzeProvince(array $province): array
    {
        $anomalies = [];
        $provinceName = $province['name_th'] ?? '';

        foreach ($province['constituencies'] ?? [] as $constituency) {
            $constAnomalies = $this->analyzeConstituency($constituency, $provinceName);
            $anomalies = array_merge($anomalies, $constAnomalies);
        }

        return $anomalies;
    }

    /**
     * วิเคราะห์ข้อมูลทั้งประเทศ
     */
    public function analyzeAll(array $data): array
    {
        $allAnomalies = [];
        $summary = [
            'total_provinces' => count($data['provinces'] ?? []),
            'total_constituencies' => 0,
            'total_anomalies' => 0,
            'critical_count' => 0,
            'warning_count' => 0,
            'info_count' => 0,
            'provinces_with_anomalies' => 0,
            'anomaly_types' => [],
        ];

        $provincesWithAnomalies = [];

        foreach ($data['provinces'] ?? [] as $province) {
            $summary['total_constituencies'] += count($province['constituencies'] ?? []);
            $anomalies = $this->analyzeProvince($province);

            if (! empty($anomalies)) {
                $provincesWithAnomalies[$province['name_th']] = true;
                $allAnomalies = array_merge($allAnomalies, $anomalies);
            }
        }

        // สรุปผล
        foreach ($allAnomalies as $anomaly) {
            $summary['total_anomalies']++;
            match ($anomaly['severity']) {
                self::SEVERITY_CRITICAL => $summary['critical_count']++,
                self::SEVERITY_WARNING => $summary['warning_count']++,
                self::SEVERITY_INFO => $summary['info_count']++,
            };

            $type = $anomaly['type'];

            if (! isset($summary['anomaly_types'][$type])) {
                $summary['anomaly_types'][$type] = 0;
            }
            $summary['anomaly_types'][$type]++;
        }

        $summary['provinces_with_anomalies'] = count($provincesWithAnomalies);

        return [
            'summary' => $summary,
            'anomalies' => $allAnomalies,
        ];
    }

    /**
     * สร้างรายงาน AI Analysis สำหรับข้อมูลเลือกตั้ง
     */
    public function generateAIAnalysis(array $data, array $anomalyResult): array
    {
        $summary = $anomalyResult['summary'];
        $anomalies = $anomalyResult['anomalies'];
        $national = $data['national_summary'] ?? [];

        $eligible = $national['eligible_voters'] ?? 0;
        $totalVoters = $national['total_voters'] ?? 0;
        $goodBallots = $national['good_ballots'] ?? 0;
        $badBallots = $national['bad_ballots'] ?? 0;
        $noVote = $national['no_vote'] ?? 0;

        $turnoutPercent = $eligible > 0 ? round(($totalVoters / $eligible) * 100, 2) : 0;
        $badBallotPercent = $totalVoters > 0 ? round(($badBallots / $totalVoters) * 100, 2) : 0;
        $noVotePercent = $goodBallots > 0 ? round(($noVote / $goodBallots) * 100, 2) : 0;

        // วิเคราะห์คะแนนรายพรรค
        $partyVotes = $this->aggregatePartyVotes($data);

        // สร้าง analysis sections
        $sections = [];

        // 1. ภาพรวม
        $sections[] = [
            'title' => 'ภาพรวมการเลือกตั้ง',
            'icon' => 'chart',
            'content' => $this->generateOverviewAnalysis($eligible, $totalVoters, $turnoutPercent, $goodBallots, $badBallots, $badBallotPercent, $noVote, $noVotePercent),
        ];

        // 2. ผลคะแนนรายพรรค
        $sections[] = [
            'title' => 'วิเคราะห์ผลคะแนนรายพรรค',
            'icon' => 'party',
            'content' => $this->generatePartyAnalysis($partyVotes, $goodBallots),
        ];

        // 3. ความผิดปกติที่พบ
        $sections[] = [
            'title' => 'สรุปความผิดปกติที่ตรวจพบ',
            'icon' => 'alert',
            'content' => $this->generateAnomalyAnalysis($summary, $anomalies),
        ];

        // 4. ข้อเสนอแนะ
        $sections[] = [
            'title' => 'ข้อเสนอแนะจากการวิเคราะห์',
            'icon' => 'recommend',
            'content' => $this->generateRecommendations($summary, $anomalies),
        ];

        // 5. วิเคราะห์รายภาค
        $sections[] = [
            'title' => 'วิเคราะห์ผลรายภูมิภาค',
            'icon' => 'region',
            'content' => $this->generateRegionalAnalysis($data),
        ];

        return [
            'generated_at' => now()->toIso8601String(),
            'overall_score' => $this->calculateIntegrityScore($summary),
            'turnout_percent' => $turnoutPercent,
            'bad_ballot_percent' => $badBallotPercent,
            'sections' => $sections,
            'party_votes' => $partyVotes,
        ];
    }

    /**
     * รวมคะแนนรายพรรค
     */
    protected function aggregatePartyVotes(array $data): array
    {
        $partyVotes = [];

        foreach ($data['provinces'] ?? [] as $province) {
            foreach ($province['constituencies'] ?? [] as $constituency) {
                foreach ($constituency['candidates'] ?? [] as $candidate) {
                    $party = $candidate['party'] ?? 'ไม่ทราบ';

                    if (! isset($partyVotes[$party])) {
                        $partyVotes[$party] = [
                            'party' => $party,
                            'party_color' => $candidate['party_color'] ?? '#666',
                            'total_votes' => 0,
                            'constituencies_won' => 0,
                        ];
                    }
                    $partyVotes[$party]['total_votes'] += $candidate['votes'] ?? 0;

                    if (! empty($candidate['is_winner'])) {
                        $partyVotes[$party]['constituencies_won']++;
                    }
                }
            }
        }

        // เรียงตามคะแนน
        usort($partyVotes, fn ($a, $b) => $b['total_votes'] <=> $a['total_votes']);

        return $partyVotes;
    }

    protected function generateOverviewAnalysis(int $eligible, int $totalVoters, float $turnoutPercent, int $goodBallots, int $badBallots, float $badBallotPercent, int $noVote, float $noVotePercent): string
    {
        $lines = [];
        $lines[] = "ผู้มีสิทธิเลือกตั้งทั้งหมด {$this->fmt($eligible)} คน มีผู้มาใช้สิทธิ {$this->fmt($totalVoters)} คน คิดเป็น {$turnoutPercent}%";

        if ($turnoutPercent >= 75) {
            $lines[] = 'อัตราการมาใช้สิทธิอยู่ในระดับสูง แสดงถึงความตื่นตัวทางการเมืองของประชาชน';
        } elseif ($turnoutPercent >= 60) {
            $lines[] = 'อัตราการมาใช้สิทธิอยู่ในระดับปานกลาง ซึ่งเป็นค่าเฉลี่ยปกติของการเลือกตั้งไทย';
        } else {
            $lines[] = 'อัตราการมาใช้สิทธิอยู่ในระดับต่ำกว่าค่าเฉลี่ย อาจเป็นสัญญาณความไม่เชื่อมั่นในระบบ';
        }

        $lines[] = "บัตรเสีย {$this->fmt($badBallots)} ใบ ({$badBallotPercent}%) " . ($badBallotPercent > 5 ? '- สูงกว่าค่าเฉลี่ยปกติ' : '- อยู่ในเกณฑ์ปกติ');
        $lines[] = "ไม่ประสงค์ลงคะแนน {$this->fmt($noVote)} คน ({$noVotePercent}%)";

        return implode("\n", $lines);
    }

    protected function generatePartyAnalysis(array $partyVotes, int $totalGoodBallots): string
    {
        $lines = [];
        $rank = 1;

        foreach (array_slice($partyVotes, 0, 10) as $party) {
            $percent = $totalGoodBallots > 0 ? round(($party['total_votes'] / $totalGoodBallots) * 100, 2) : 0;
            $lines[] = "อันดับ {$rank}: {$party['party']} - {$this->fmt($party['total_votes'])} คะแนน ({$percent}%) ชนะ {$party['constituencies_won']} เขต";
            $rank++;
        }

        if (count($partyVotes) >= 2) {
            $first = $partyVotes[0];
            $second = $partyVotes[1];
            $gap = $first['total_votes'] - $second['total_votes'];
            $lines[] = "\nพรรค{$first['party']}นำพรรค{$second['party']}อยู่ {$this->fmt($gap)} คะแนน";
        }

        return implode("\n", $lines);
    }

    protected function generateAnomalyAnalysis(array $summary, array $anomalies): string
    {
        $lines = [];

        if ($summary['total_anomalies'] === 0) {
            $lines[] = 'ไม่พบความผิดปกติในข้อมูลผลเลือกตั้ง ข้อมูลมีความสอดคล้องกันดี';

            return implode("\n", $lines);
        }

        $lines[] = "พบความผิดปกติทั้งหมด {$summary['total_anomalies']} รายการ ใน {$summary['provinces_with_anomalies']} จังหวัด:";
        $lines[] = "- ระดับวิกฤต (Critical): {$summary['critical_count']} รายการ";
        $lines[] = "- ระดับเตือน (Warning): {$summary['warning_count']} รายการ";
        $lines[] = "- ระดับข้อมูล (Info): {$summary['info_count']} รายการ";

        // สรุปตามประเภท
        $typeNames = [
            'voters_exceed_eligible' => 'ผู้มาใช้สิทธิมากกว่าผู้มีสิทธิ',
            'ballot_sum_mismatch' => 'จำนวนบัตรไม่ตรงกับผู้มาใช้สิทธิ',
            'vote_sum_mismatch' => 'ผลรวมคะแนนไม่ตรงกับบัตรดี',
            'winner_exceeds_good_ballots' => 'คะแนนผู้ชนะมากกว่าบัตรดี',
            'abnormal_high_turnout' => 'อัตราผู้มาใช้สิทธิสูงผิดปกติ',
            'abnormal_low_turnout' => 'อัตราผู้มาใช้สิทธิต่ำผิดปกติ',
            'abnormal_bad_ballot_rate' => 'อัตราบัตรเสียสูงผิดปกติ',
            'zero_votes_candidate' => 'ผู้สมัครได้ 0 คะแนน',
            'abnormal_winner_percentage' => 'ผู้ชนะได้คะแนนสูงผิดปกติ',
        ];

        $lines[] = "\nรายละเอียดตามประเภท:";

        foreach ($summary['anomaly_types'] as $type => $count) {
            $name = $typeNames[$type] ?? $type;
            $lines[] = "- {$name}: {$count} เขต";
        }

        // แสดง critical anomalies
        $criticals = array_filter($anomalies, fn ($a) => $a['severity'] === self::SEVERITY_CRITICAL);

        if (! empty($criticals)) {
            $lines[] = "\nความผิดปกติระดับวิกฤตที่ต้องตรวจสอบเร่งด่วน:";

            foreach ($criticals as $a) {
                $lines[] = "⚠ {$a['location']}: {$a['message']}";
            }
        }

        return implode("\n", $lines);
    }

    protected function generateRecommendations(array $summary, array $anomalies): string
    {
        $lines = [];

        if ($summary['critical_count'] > 0) {
            $lines[] = "1. ควรตรวจสอบเขตที่มีความผิดปกติระดับวิกฤต ({$summary['critical_count']} เขต) โดยเร่งด่วน เนื่องจากตัวเลขไม่สอดคล้องกันทางคณิตศาสตร์";
        }

        $criticals = array_filter($anomalies, fn ($a) => $a['severity'] === self::SEVERITY_CRITICAL);
        $criticalProvinces = array_unique(array_column($criticals, 'province'));

        if (! empty($criticalProvinces)) {
            $lines[] = '2. จังหวัดที่ต้องตรวจสอบเป็นพิเศษ: ' . implode(', ', $criticalProvinces);
        }

        if ($summary['warning_count'] > 0) {
            $lines[] = "3. เขตที่มีความผิดปกติระดับเตือน ({$summary['warning_count']} เขต) ควรได้รับการตรวจสอบเพิ่มเติม แม้อาจมีคำอธิบายที่สมเหตุสมผล";
        }

        $lines[] = '4. ควรเปรียบเทียบข้อมูลกับแบบ 69 ฉบับจริงที่ กกต. ประกาศอย่างเป็นทางการ';
        $lines[] = '5. ข้อมูลที่ผิดปกติไม่ได้หมายความว่ามีการทุจริตเสมอไป อาจเกิดจากข้อผิดพลาดในการบันทึกข้อมูล';

        if ($summary['total_anomalies'] === 0) {
            $lines = ['ข้อมูลผลเลือกตั้งมีความสอดคล้องกันดี ไม่พบสิ่งผิดปกติที่ต้องตรวจสอบเพิ่มเติม'];
        }

        return implode("\n", $lines);
    }

    protected function generateRegionalAnalysis(array $data): string
    {
        $regions = [];
        $regionNames = [
            'north' => 'ภาคเหนือ',
            'northeast' => 'ภาคตะวันออกเฉียงเหนือ',
            'central' => 'ภาคกลาง',
            'east' => 'ภาคตะวันออก',
            'west' => 'ภาคตะวันตก',
            'south' => 'ภาคใต้',
        ];

        foreach ($data['provinces'] ?? [] as $province) {
            $region = $province['region'] ?? 'unknown';

            if (! isset($regions[$region])) {
                $regions[$region] = [
                    'provinces' => 0,
                    'eligible' => 0,
                    'voters' => 0,
                    'constituencies' => 0,
                ];
            }
            $regions[$region]['provinces']++;
            $regions[$region]['eligible'] += $province['summary']['eligible_voters'] ?? 0;
            $regions[$region]['voters'] += $province['summary']['total_voters'] ?? 0;
            $regions[$region]['constituencies'] += count($province['constituencies'] ?? []);
        }

        $lines = [];

        foreach ($regions as $key => $r) {
            $name = $regionNames[$key] ?? $key;
            $turnout = $r['eligible'] > 0 ? round(($r['voters'] / $r['eligible']) * 100, 1) : 0;
            $lines[] = "{$name}: {$r['provinces']} จังหวัด {$r['constituencies']} เขต - อัตราใช้สิทธิ {$turnout}%";
        }

        return implode("\n", $lines);
    }

    /**
     * คำนวณคะแนนความน่าเชื่อถือ (0-100)
     */
    protected function calculateIntegrityScore(array $summary): int
    {
        $score = 100;

        // หัก critical: -15 ต่อรายการ
        $score -= $summary['critical_count'] * 15;

        // หัก warning: -5 ต่อรายการ
        $score -= $summary['warning_count'] * 5;

        // หัก info: -1 ต่อรายการ
        $score -= $summary['info_count'];

        return max(0, min(100, $score));
    }

    protected function fmt(int $number): string
    {
        return number_format($number);
    }
}
