/**
 * ข้อมูลรายงานผลการเลือกตั้ง แบบ 69 (ECT Report 69)
 * การเลือกตั้งสมาชิกสภาผู้แทนราษฎร พ.ศ. 2569
 * วันที่ 8 กุมภาพันธ์ 2569 (2026-02-08)
 *
 * หมายเหตุ: ข้อมูลจำลองสำหรับระบบทดสอบ
 * มีข้อมูลผิดปกติ (anomalies) ในบางเขตเลือกตั้งเพื่อทดสอบระบบตรวจจับ
 *
 * ความผิดปกติที่ฝังไว้:
 *   - จ.บุรีรัมย์ (id:11) เขต 3      : คะแนนผู้ชนะ (45,000) > บัตรดี (42,000)
 *   - กรุงเทพฯ (id:30) เขต 15        : ผู้มาใช้สิทธิ (92,000) > ผู้มีสิทธิ (85,000)
 *   - จ.ขอนแก่น (id:20) เขต 5        : บัตรดี + บัตรเสีย (78,000) > ผู้มาใช้สิทธิ (75,000)
 *   - จ.นครศรีธรรมราช (id:64) เขต 7  : คะแนนรวม + ไม่ประสงค์ (72,100) > บัตรดี (69,500)
 *   - จ.สงขลา (id:71) เขต 4          : อัตราการมาใช้สิทธิสูงผิดปกติ (99.5%)
 *   - จ.ตาก (id:43) เขต 2            : บัตรเสียสูงผิดปกติ (15%)
 *   - จ.ปัตตานี (id:75) เขต 2        : ผู้มาใช้สิทธิต่ำมาก (25%)
 *   - จ.สุพรรณบุรี (id:48) เขต 3     : ผู้สมัครได้ 0 คะแนน
 */

// =============================================================================
// ตัวสร้างเลขสุ่มแบบกำหนดค่าเริ่มต้น (Mulberry32 PRNG)
// ให้ข้อมูลคงที่ทุกครั้งที่โหลดหน้า
// =============================================================================

function mulberry32(seed) {
    return function () {
        seed |= 0;
        seed = (seed + 0x6d2b79f5) | 0;
        let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** สุ่มเลขจำนวนเต็มในช่วง [min, max] */
function randInt(rng, min, max) {
    return Math.floor(rng() * (max - min + 1)) + min;
}

// =============================================================================
// ข้อมูลพรรคการเมือง 6 พรรคหลัก
// =============================================================================

const PARTIES = [
    { name: 'พรรคประชาชน', abbr: 'PP', color: '#FF6B00' },
    { name: 'พรรคเพื่อไทย', abbr: 'PT', color: '#E31E25' },
    { name: 'พรรคภูมิใจไทย', abbr: 'BJT', color: '#0066B3' },
    { name: 'พรรคพลังประชารัฐ', abbr: 'PPRP', color: '#1E3A8A' },
    { name: 'พรรครวมไทยสร้างชาติ', abbr: 'UTN', color: '#6B21A8' },
    { name: 'พรรคประชาธิปัตย์', abbr: 'DP', color: '#00AEEF' },
];

// =============================================================================
// ชื่อผู้สมัครจำลอง (สำหรับสุ่ม)
// =============================================================================

const FIRST_NAMES = [
    'สมชาย',
    'วิชัย',
    'ประเสริฐ',
    'สุรชัย',
    'วัฒนา',
    'อนุชา',
    'กิตติ',
    'พิชัย',
    'ธนกร',
    'ณัฐพล',
    'สุทธิ',
    'ชัยวัฒน์',
    'อภิชาติ',
    'วรพล',
    'มนตรี',
    'ศักดิ์ชัย',
    'ปรีชา',
    'สมศักดิ์',
    'เจริญ',
    'ธวัชชัย',
    'สุภาพร',
    'นภาพร',
    'วิไลวรรณ',
    'สุกัญญา',
    'พรทิพย์',
    'อรุณี',
    'จิราพร',
    'นิตยา',
    'กาญจนา',
    'สุดารัตน์',
    'ชาตรี',
    'สุริยา',
    'อำนวย',
    'บุญเลิศ',
    'วิโรจน์',
    'สุวรรณ',
    'ธีระ',
    'พงษ์ศักดิ์',
    'สมพร',
    'จำลอง',
    'ศิริ',
    'สุชาติ',
    'อดิศร',
    'เกรียงไกร',
    'นพดล',
    'ภานุวัฒน์',
    'อนุสรณ์',
    'กฤษฎา',
    'รัตนา',
    'วิภาดา',
];

const LAST_NAMES = [
    'สุขใจ',
    'วงศ์สวัสดิ์',
    'พิทักษ์ไทย',
    'เจริญสุข',
    'ศรีสุข',
    'แก้วมณี',
    'บุญมา',
    'ชัยศรี',
    'วรรณกุล',
    'ศักดิ์เสนา',
    'ประเสริฐกุล',
    'ธนภัทร',
    'สิทธิชัย',
    'มงคลชัย',
    'ศรีประเสริฐ',
    'พันธุ์ทอง',
    'เกตุทอง',
    'วิริยะ',
    'กิตติศักดิ์',
    'จันทร์เพ็ง',
    'สว่างแจ้ง',
    'ทองดี',
    'ลิ้มเจริญ',
    'แซ่ตั้ง',
    'ชูศรี',
    'อุดมศักดิ์',
    'พรหมมา',
    'แก้วเกิด',
    'บุญเรือง',
    'ศรีวิไล',
    'รัตนวงศ์',
    'คำแก้ว',
    'ใจดี',
    'นาคสุข',
    'เพชรดี',
];

// =============================================================================
// ข้อมูลจังหวัดทั้ง 77 จังหวัด
// รูปแบบ: [id, ชื่อไทย, ชื่ออังกฤษ, ภาค, จำนวนเขตเลือกตั้ง]
// =============================================================================

const PROVINCE_META = [
    // ภาคเหนือ (9 จังหวัด)
    [1, 'เชียงใหม่', 'Chiang Mai', 'ภาคเหนือ', 10],
    [2, 'ลำพูน', 'Lamphun', 'ภาคเหนือ', 2],
    [3, 'ลำปาง', 'Lampang', 'ภาคเหนือ', 4],
    [4, 'อุตรดิตถ์', 'Uttaradit', 'ภาคเหนือ', 3],
    [5, 'แพร่', 'Phrae', 'ภาคเหนือ', 3],
    [6, 'น่าน', 'Nan', 'ภาคเหนือ', 3],
    [7, 'พะเยา', 'Phayao', 'ภาคเหนือ', 3],
    [8, 'เชียงราย', 'Chiang Rai', 'ภาคเหนือ', 7],
    [9, 'แม่ฮ่องสอน', 'Mae Hong Son', 'ภาคเหนือ', 1],

    // ภาคตะวันออกเฉียงเหนือ (20 จังหวัด)
    [10, 'นครราชสีมา', 'Nakhon Ratchasima', 'ภาคตะวันออกเฉียงเหนือ', 16],
    [11, 'บุรีรัมย์', 'Buriram', 'ภาคตะวันออกเฉียงเหนือ', 10],
    [12, 'สุรินทร์', 'Surin', 'ภาคตะวันออกเฉียงเหนือ', 8],
    [13, 'ศรีสะเกษ', 'Si Sa Ket', 'ภาคตะวันออกเฉียงเหนือ', 9],
    [14, 'อุบลราชธานี', 'Ubon Ratchathani', 'ภาคตะวันออกเฉียงเหนือ', 11],
    [15, 'ยโสธร', 'Yasothon', 'ภาคตะวันออกเฉียงเหนือ', 3],
    [16, 'ชัยภูมิ', 'Chaiyaphum', 'ภาคตะวันออกเฉียงเหนือ', 7],
    [17, 'อำนาจเจริญ', 'Amnat Charoen', 'ภาคตะวันออกเฉียงเหนือ', 2],
    [18, 'บึงกาฬ', 'Bueng Kan', 'ภาคตะวันออกเฉียงเหนือ', 2],
    [19, 'หนองบัวลำภู', 'Nong Bua Lamphu', 'ภาคตะวันออกเฉียงเหนือ', 3],
    [20, 'ขอนแก่น', 'Khon Kaen', 'ภาคตะวันออกเฉียงเหนือ', 11],
    [21, 'อุดรธานี', 'Udon Thani', 'ภาคตะวันออกเฉียงเหนือ', 10],
    [22, 'เลย', 'Loei', 'ภาคตะวันออกเฉียงเหนือ', 4],
    [23, 'หนองคาย', 'Nong Khai', 'ภาคตะวันออกเฉียงเหนือ', 3],
    [24, 'มหาสารคาม', 'Maha Sarakham', 'ภาคตะวันออกเฉียงเหนือ', 6],
    [25, 'ร้อยเอ็ด', 'Roi Et', 'ภาคตะวันออกเฉียงเหนือ', 8],
    [26, 'กาฬสินธุ์', 'Kalasin', 'ภาคตะวันออกเฉียงเหนือ', 6],
    [27, 'สกลนคร', 'Sakon Nakhon', 'ภาคตะวันออกเฉียงเหนือ', 7],
    [28, 'นครพนม', 'Nakhon Phanom', 'ภาคตะวันออกเฉียงเหนือ', 4],
    [29, 'มุกดาหาร', 'Mukdahan', 'ภาคตะวันออกเฉียงเหนือ', 2],

    // ภาคกลาง (22 จังหวัด)
    [30, 'กรุงเทพมหานคร', 'Bangkok', 'ภาคกลาง', 33],
    [31, 'สมุทรปราการ', 'Samut Prakan', 'ภาคกลาง', 8],
    [32, 'นนทบุรี', 'Nonthaburi', 'ภาคกลาง', 8],
    [33, 'ปทุมธานี', 'Pathum Thani', 'ภาคกลาง', 7],
    [34, 'พระนครศรีอยุธยา', 'Phra Nakhon Si Ayutthaya', 'ภาคกลาง', 5],
    [35, 'อ่างทอง', 'Ang Thong', 'ภาคกลาง', 2],
    [36, 'ลพบุรี', 'Lopburi', 'ภาคกลาง', 4],
    [37, 'สิงห์บุรี', 'Sing Buri', 'ภาคกลาง', 1],
    [38, 'ชัยนาท', 'Chai Nat', 'ภาคกลาง', 2],
    [39, 'สระบุรี', 'Saraburi', 'ภาคกลาง', 4],
    [40, 'นครสวรรค์', 'Nakhon Sawan', 'ภาคกลาง', 6],
    [41, 'อุทัยธานี', 'Uthai Thani', 'ภาคกลาง', 2],
    [42, 'กำแพงเพชร', 'Kamphaeng Phet', 'ภาคกลาง', 4],
    [43, 'ตาก', 'Tak', 'ภาคกลาง', 3],
    [44, 'สุโขทัย', 'Sukhothai', 'ภาคกลาง', 4],
    [45, 'พิษณุโลก', 'Phitsanulok', 'ภาคกลาง', 5],
    [46, 'พิจิตร', 'Phichit', 'ภาคกลาง', 3],
    [47, 'เพชรบูรณ์', 'Phetchabun', 'ภาคกลาง', 6],
    [48, 'สุพรรณบุรี', 'Suphan Buri', 'ภาคกลาง', 5],
    [49, 'นครปฐม', 'Nakhon Pathom', 'ภาคกลาง', 6],
    [50, 'สมุทรสาคร', 'Samut Sakhon', 'ภาคกลาง', 3],
    [51, 'สมุทรสงคราม', 'Samut Songkhram', 'ภาคกลาง', 1],

    // ภาคตะวันออก (8 จังหวัด)
    [52, 'ชลบุรี', 'Chon Buri', 'ภาคตะวันออก', 9],
    [53, 'ระยอง', 'Rayong', 'ภาคตะวันออก', 4],
    [54, 'จันทบุรี', 'Chanthaburi', 'ภาคตะวันออก', 3],
    [55, 'ตราด', 'Trat', 'ภาคตะวันออก', 1],
    [56, 'ฉะเชิงเทรา', 'Chachoengsao', 'ภาคตะวันออก', 4],
    [57, 'ปราจีนบุรี', 'Prachin Buri', 'ภาคตะวันออก', 3],
    [58, 'นครนายก', 'Nakhon Nayok', 'ภาคตะวันออก', 2],
    [59, 'สระแก้ว', 'Sa Kaeo', 'ภาคตะวันออก', 3],

    // ภาคตะวันตก (4 จังหวัด)
    [60, 'ราชบุรี', 'Ratchaburi', 'ภาคตะวันตก', 5],
    [61, 'กาญจนบุรี', 'Kanchanaburi', 'ภาคตะวันตก', 5],
    [62, 'เพชรบุรี', 'Phetchaburi', 'ภาคตะวันตก', 3],
    [63, 'ประจวบคีรีขันธ์', 'Prachuap Khiri Khan', 'ภาคตะวันตก', 3],

    // ภาคใต้ (14 จังหวัด)
    [64, 'นครศรีธรรมราช', 'Nakhon Si Thammarat', 'ภาคใต้', 10],
    [65, 'กระบี่', 'Krabi', 'ภาคใต้', 3],
    [66, 'พังงา', 'Phang Nga', 'ภาคใต้', 2],
    [67, 'ภูเก็ต', 'Phuket', 'ภาคใต้', 3],
    [68, 'สุราษฎร์ธานี', 'Surat Thani', 'ภาคใต้', 6],
    [69, 'ระนอง', 'Ranong', 'ภาคใต้', 1],
    [70, 'ชุมพร', 'Chumphon', 'ภาคใต้', 3],
    [71, 'สงขลา', 'Songkhla', 'ภาคใต้', 9],
    [72, 'สตูล', 'Satun', 'ภาคใต้', 2],
    [73, 'ตรัง', 'Trang', 'ภาคใต้', 4],
    [74, 'พัทลุง', 'Phatthalung', 'ภาคใต้', 3],
    [75, 'ปัตตานี', 'Pattani', 'ภาคใต้', 4],
    [76, 'ยะลา', 'Yala', 'ภาคใต้', 3],
    [77, 'นราธิวาส', 'Narathiwat', 'ภาคใต้', 4],
];

// =============================================================================
// ฟังก์ชันสร้างชื่อผู้สมัครจำลอง
// =============================================================================

function generateCandidateName(rng) {
    const first = FIRST_NAMES[Math.floor(rng() * FIRST_NAMES.length)];
    const last = LAST_NAMES[Math.floor(rng() * LAST_NAMES.length)];
    return `${first} ${last}`;
}

// =============================================================================
// ฟังก์ชันเลือกพรรคสำหรับเขตเลือกตั้ง
// สุ่มเลือกพรรคพร้อมแนวโน้มตามภูมิภาค (พรรคแรก = ผู้ชนะ)
// =============================================================================

function selectParties(rng, region, count) {
    // ลำดับพรรคตามแนวโน้มภูมิภาค (ดัชนี 0-5 ตาม PARTIES)
    const regionBias = {
        ภาคเหนือ: [0, 1, 2, 4, 3, 5], // PP, PT แข็งแกร่ง
        ภาคตะวันออกเฉียงเหนือ: [1, 0, 2, 3, 4, 5], // PT, PP แข็งแกร่ง
        ภาคกลาง: [0, 1, 3, 2, 4, 5], // PP, PT, PPRP ผสม
        ภาคตะวันออก: [2, 0, 1, 3, 4, 5], // BJT, PP ผสม
        ภาคตะวันตก: [2, 3, 0, 1, 4, 5], // BJT, PPRP แข็งแกร่ง
        ภาคใต้: [5, 2, 4, 0, 1, 3], // DP, BJT แข็งแกร่ง
    };

    const baseOrder = regionBias[region] || [0, 1, 2, 3, 4, 5];
    const indices = [...baseOrder];

    // เพิ่มความหลากหลาย: สลับบางตำแหน่งตามค่าสุ่ม
    for (let i = 0; i < indices.length; i++) {
        if (rng() < 0.25) {
            const j = Math.floor(rng() * indices.length);
            [indices[i], indices[j]] = [indices[j], indices[i]];
        }
    }

    return indices.slice(0, count).map((i) => PARTIES[i]);
}

// =============================================================================
// ฟังก์ชันสร้างข้อมูลเขตเลือกตั้งปกติ
// ใช้ seed จาก provinceId + constNumber เพื่อความคงที่
//
// ค่าปกติ:
//   - ผู้มีสิทธิ: 70,000-120,000 (กทม. 90,000-150,000)
//   - อัตราการมาใช้สิทธิ: 60-80%
//   - อัตราบัตรเสีย: 1-4%
//   - อัตราไม่ประสงค์ลงคะแนน: 2-5% ของบัตรดี
//   - บัตรดี + บัตรเสีย = ผู้มาใช้สิทธิ (เสมอ)
//   - ผลรวมคะแนนผู้สมัคร + ไม่ประสงค์ = บัตรดี (เสมอ)
// =============================================================================

function generateNormalConstituency(provinceId, constNumber, region) {
    // สร้าง PRNG จาก seed ที่กำหนด
    const seed = provinceId * 1000 + constNumber;
    const rng = mulberry32(seed);

    // จำนวนหน่วยเลือกตั้ง (กทม. มีมากกว่า)
    const isBangkok = provinceId === 30;
    const totalStations = randInt(rng, isBangkok ? 120 : 80, isBangkok ? 200 : 180);

    // ผู้มีสิทธิเลือกตั้ง
    const baseEligible = isBangkok ? 120000 : 95000;
    const eligibleVariance = isBangkok ? 30000 : 25000;
    const eligibleVoters = randInt(
        rng,
        baseEligible - eligibleVariance,
        baseEligible + eligibleVariance
    );

    // อัตราการมาใช้สิทธิ 60-80%
    const turnoutRate = 0.6 + rng() * 0.2;
    const totalVoters = Math.round(eligibleVoters * turnoutRate);

    // บัตรเสีย 1-4% ของผู้มาใช้สิทธิ
    const badRate = 0.01 + rng() * 0.03;
    const badBallots = Math.round(totalVoters * badRate);

    // บัตรดี = ผู้มาใช้สิทธิ - บัตรเสีย (ต้องเท่ากันเสมอ)
    const goodBallots = totalVoters - badBallots;

    // ไม่ประสงค์ลงคะแนน 2-5% ของบัตรดี
    const noVoteRate = 0.02 + rng() * 0.03;
    const noVote = Math.round(goodBallots * noVoteRate);

    // คะแนนรวมที่แจกให้ผู้สมัคร = บัตรดี - ไม่ประสงค์ลงคะแนน
    const totalCandidateVotes = goodBallots - noVote;

    // จำนวนผู้สมัคร 3-5 คน
    const numCandidates = randInt(rng, 3, 5);
    const selectedParties = selectParties(rng, region, numCandidates);

    // แจกคะแนน: ผู้ชนะ ~35-50%, ที่เหลือแบ่งกัน
    const winnerShare = 0.35 + rng() * 0.15;
    const winnerVotes = Math.round(totalCandidateVotes * winnerShare);
    let remaining = totalCandidateVotes - winnerVotes;

    const voteShares = [winnerVotes];
    for (let i = 1; i < numCandidates; i++) {
        if (i === numCandidates - 1) {
            // ผู้สมัครคนสุดท้ายรับคะแนนที่เหลือทั้งหมด
            voteShares.push(remaining);
        } else {
            const share = Math.round(remaining * (0.2 + rng() * 0.4));
            voteShares.push(share);
            remaining -= share;
        }
    }

    // สร้างอาร์เรย์ผู้สมัคร
    const candidates = selectedParties.map((party, idx) => ({
        number: idx + 1,
        name: generateCandidateName(rng),
        party: party.name,
        party_color: party.color,
        votes: voteShares[idx],
        is_winner: idx === 0,
    }));

    return {
        number: constNumber,
        total_stations: totalStations,
        counted_stations: totalStations, // นับครบทุกหน่วย
        eligible_voters: eligibleVoters,
        total_voters: totalVoters,
        good_ballots: goodBallots,
        bad_ballots: badBallots,
        no_vote: noVote,
        candidates,
    };
}

// =============================================================================
// ข้อมูลเขตเลือกตั้งที่มีความผิดปกติ (Anomalies)
// กำหนดค่าคงที่เพื่อทดสอบระบบตรวจจับ
// =============================================================================

function getAnomalyData() {
    return {
        // =========================================================================
        // Anomaly 1: บุรีรัมย์ เขต 3
        // ปัญหา: คะแนนผู้ชนะ (45,000) > บัตรดี (42,000)
        // =========================================================================
        '11_3': {
            number: 3,
            total_stations: 120,
            counted_stations: 120,
            eligible_voters: 95000,
            total_voters: 68000,
            good_ballots: 42000, // *** คะแนนผู้ชนะ 45,000 เกินบัตรดี ***
            bad_ballots: 1800,
            no_vote: 1200,
            candidates: [
                {
                    number: 1,
                    name: 'ชัยวัฒน์ วงศ์สวัสดิ์',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 45000, // *** ผิดปกติ: 45,000 > good_ballots 42,000 ***
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'สมชาย เจริญสุข',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 15000,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'วิชัย พิทักษ์ไทย',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 8000,
                    is_winner: false,
                },
                {
                    number: 4,
                    name: 'ประเสริฐ ศรีสุข',
                    party: 'พรรคพลังประชารัฐ',
                    party_color: '#1E3A8A',
                    votes: 3500,
                    is_winner: false,
                },
            ],
        },

        // =========================================================================
        // Anomaly 2: กรุงเทพมหานคร เขต 15
        // ปัญหา: ผู้มาใช้สิทธิ (92,000) > ผู้มีสิทธิ (85,000)
        // =========================================================================
        '30_15': {
            number: 15,
            total_stations: 150,
            counted_stations: 150,
            eligible_voters: 85000, // *** ผิดปกติ: น้อยกว่า total_voters ***
            total_voters: 92000, // *** ผิดปกติ: 92,000 > eligible_voters 85,000 ***
            good_ballots: 89500,
            bad_ballots: 2500,
            no_vote: 2800,
            candidates: [
                {
                    number: 1,
                    name: 'ธนกร ประเสริฐกุล',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 38000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'พรทิพย์ ธนภัทร',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 28000,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'สุรชัย แก้วมณี',
                    party: 'พรรครวมไทยสร้างชาติ',
                    party_color: '#6B21A8',
                    votes: 15000,
                    is_winner: false,
                },
                {
                    number: 4,
                    name: 'กิตติ ชัยศรี',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 5700,
                    is_winner: false,
                },
            ],
        },

        // =========================================================================
        // Anomaly 3: ขอนแก่น เขต 5
        // ปัญหา: บัตรดี + บัตรเสีย (78,000) > ผู้มาใช้สิทธิ (75,000)
        // =========================================================================
        '20_5': {
            number: 5,
            total_stations: 130,
            counted_stations: 130,
            eligible_voters: 105000,
            total_voters: 75000,
            good_ballots: 73000, // *** ผิดปกติ: 73,000 + 5,000 = 78,000 > total_voters 75,000 ***
            bad_ballots: 5000,
            no_vote: 2200,
            candidates: [
                {
                    number: 1,
                    name: 'อนุชา สิทธิชัย',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 32000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'ณัฐพล มงคลชัย',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 22000,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'สุทธิ ศรีประเสริฐ',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 12000,
                    is_winner: false,
                },
                {
                    number: 4,
                    name: 'วรพล บุญมา',
                    party: 'พรรคพลังประชารัฐ',
                    party_color: '#1E3A8A',
                    votes: 4800,
                    is_winner: false,
                },
            ],
        },

        // =========================================================================
        // Anomaly 4: นครศรีธรรมราช เขต 7
        // ปัญหา: ผลรวมคะแนนผู้สมัคร + ไม่ประสงค์ (72,100) > บัตรดี (69,500)
        // คำนวณ: 35,000 + 20,000 + 10,000 + 5,000 + 2,100 = 72,100
        // =========================================================================
        '64_7': {
            number: 7,
            total_stations: 110,
            counted_stations: 110,
            eligible_voters: 98000,
            total_voters: 72000,
            good_ballots: 69500,
            bad_ballots: 2500,
            no_vote: 2100,
            // *** ผิดปกติ: 35,000+20,000+10,000+5,000 + 2,100 = 72,100 > good_ballots 69,500 ***
            candidates: [
                {
                    number: 1,
                    name: 'สุภาพร พันธุ์ทอง',
                    party: 'พรรคประชาธิปัตย์',
                    party_color: '#00AEEF',
                    votes: 35000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'อภิชาติ เกตุทอง',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 20000,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'มนตรี วิริยะ',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 10000,
                    is_winner: false,
                },
                {
                    number: 4,
                    name: 'จิราพร กิตติศักดิ์',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 5000,
                    is_winner: false,
                },
            ],
        },

        // =========================================================================
        // Anomaly 5: สงขลา เขต 4
        // ปัญหา: อัตราการมาใช้สิทธิสูงผิดปกติ 99.5% (99,500 / 100,000)
        // =========================================================================
        '71_4': {
            number: 4,
            total_stations: 125,
            counted_stations: 125,
            eligible_voters: 100000,
            total_voters: 99500, // *** ผิดปกติ: turnout 99.5% ***
            good_ballots: 97000,
            bad_ballots: 2500,
            no_vote: 3000,
            candidates: [
                {
                    number: 1,
                    name: 'ศักดิ์ชัย จันทร์เพ็ง',
                    party: 'พรรคประชาธิปัตย์',
                    party_color: '#00AEEF',
                    votes: 42000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'ปรีชา สว่างแจ้ง',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 30000,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'สมศักดิ์ ทองดี',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 15000,
                    is_winner: false,
                },
                {
                    number: 4,
                    name: 'นภาพร ลิ้มเจริญ',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 7000,
                    is_winner: false,
                },
            ],
        },

        // =========================================================================
        // Anomaly 6: ตาก เขต 2
        // ปัญหา: อัตราบัตรเสียสูงผิดปกติ 15% (9,300 / 62,000)
        // =========================================================================
        '43_2': {
            number: 2,
            total_stations: 100,
            counted_stations: 100,
            eligible_voters: 88000,
            total_voters: 62000,
            good_ballots: 52700,
            bad_ballots: 9300, // *** ผิดปกติ: 9,300 / 62,000 = 15% ***
            no_vote: 1600,
            candidates: [
                {
                    number: 1,
                    name: 'เจริญ แซ่ตั้ง',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 22000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'ธวัชชัย ชูศรี',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 16000,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'กาญจนา อุดมศักดิ์',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 8500,
                    is_winner: false,
                },
                {
                    number: 4,
                    name: 'สุชาติ พรหมมา',
                    party: 'พรรคพลังประชารัฐ',
                    party_color: '#1E3A8A',
                    votes: 4600,
                    is_winner: false,
                },
            ],
        },

        // =========================================================================
        // Anomaly 7: ปัตตานี เขต 2
        // ปัญหา: อัตราการมาใช้สิทธิต่ำมาก 25% (23,000 / 92,000)
        // =========================================================================
        '75_2': {
            number: 2,
            total_stations: 95,
            counted_stations: 95,
            eligible_voters: 92000,
            total_voters: 23000, // *** ผิดปกติ: turnout 25% ***
            good_ballots: 22200,
            bad_ballots: 800,
            no_vote: 700,
            candidates: [
                {
                    number: 1,
                    name: 'อดิศร แก้วเกิด',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 9500,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'นิตยา บุญเรือง',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 6000,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'เกรียงไกร ศรีวิไล',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 4000,
                    is_winner: false,
                },
                {
                    number: 4,
                    name: 'รัตนา รัตนวงศ์',
                    party: 'พรรคประชาธิปัตย์',
                    party_color: '#00AEEF',
                    votes: 2000,
                    is_winner: false,
                },
            ],
        },

        // =========================================================================
        // Anomaly 8: สุพรรณบุรี เขต 3
        // ปัญหา: ผู้สมัครหมายเลข 4 ได้ 0 คะแนน (น่าสงสัย)
        // =========================================================================
        '48_3': {
            number: 3,
            total_stations: 115,
            counted_stations: 115,
            eligible_voters: 96000,
            total_voters: 69000,
            good_ballots: 67000,
            bad_ballots: 2000,
            no_vote: 2100,
            candidates: [
                {
                    number: 1,
                    name: 'สุดารัตน์ คำแก้ว',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 35000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'วิโรจน์ ใจดี',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 18000,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'สุวรรณ นาคสุข',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 9900,
                    is_winner: false,
                },
                {
                    number: 4,
                    name: 'ธีระ เพชรดี',
                    party: 'พรรคพลังประชารัฐ',
                    party_color: '#1E3A8A',
                    votes: 0, // *** ผิดปกติ: 0 คะแนน ***
                    is_winner: false,
                },
                {
                    number: 5,
                    name: 'พงษ์ศักดิ์ สุขใจ',
                    party: 'พรรครวมไทยสร้างชาติ',
                    party_color: '#6B21A8',
                    votes: 2000,
                    is_winner: false,
                },
            ],
        },
    };
}

// =============================================================================
// ฟังก์ชันหลัก: สร้างรายงานทั้งหมด
// คำนวณสรุปจังหวัดและสรุประดับชาติจากข้อมูลเขต
// =============================================================================

function buildReport() {
    const anomalies = getAnomalyData();

    // สร้างข้อมูลจังหวัดทั้ง 77 จังหวัด
    const provinces = PROVINCE_META.map(([id, nameTh, nameEn, region, numConst]) => {
        // สร้างข้อมูลเขตเลือกตั้งทุกเขตในจังหวัด
        const constituencies = [];
        for (let c = 1; c <= numConst; c++) {
            const anomalyKey = `${id}_${c}`;
            if (anomalies[anomalyKey]) {
                // ใช้ข้อมูลผิดปกติที่กำหนดไว้
                constituencies.push(anomalies[anomalyKey]);
            } else {
                // สร้างข้อมูลปกติด้วย PRNG
                constituencies.push(generateNormalConstituency(id, c, region));
            }
        }

        // คำนวณสรุปจังหวัดจากผลรวมทุกเขต
        const summary = constituencies.reduce(
            (acc, c) => ({
                eligible_voters: acc.eligible_voters + c.eligible_voters,
                total_voters: acc.total_voters + c.total_voters,
                good_ballots: acc.good_ballots + c.good_ballots,
                bad_ballots: acc.bad_ballots + c.bad_ballots,
                no_vote: acc.no_vote + c.no_vote,
            }),
            {
                eligible_voters: 0,
                total_voters: 0,
                good_ballots: 0,
                bad_ballots: 0,
                no_vote: 0,
            }
        );

        return {
            id,
            name_th: nameTh,
            name_en: nameEn,
            region,
            total_constituencies: numConst,
            counted: numConst,
            summary,
            constituencies,
        };
    });

    // คำนวณสรุประดับชาติจากผลรวมทุกจังหวัด
    const national_summary = provinces.reduce(
        (acc, p) => ({
            eligible_voters: acc.eligible_voters + p.summary.eligible_voters,
            total_voters: acc.total_voters + p.summary.total_voters,
            good_ballots: acc.good_ballots + p.summary.good_ballots,
            bad_ballots: acc.bad_ballots + p.summary.bad_ballots,
            no_vote: acc.no_vote + p.summary.no_vote,
        }),
        {
            eligible_voters: 0,
            total_voters: 0,
            good_ballots: 0,
            bad_ballots: 0,
            no_vote: 0,
        }
    );

    // คำนวณจำนวนเขตเลือกตั้งทั้งหมดจากข้อมูลจริง
    const total_constituencies = provinces.reduce((sum, p) => sum + p.total_constituencies, 0);

    return {
        election_name: 'การเลือกตั้ง ส.ส. 2569',
        election_date: '2026-02-08',
        report_time: '2026-02-08T23:45:00+07:00',
        total_constituencies,
        counted_constituencies: total_constituencies,
        national_summary,
        provinces,
    };
}

// =============================================================================
// Export ข้อมูลรายงาน
// สร้างครั้งเดียวตอน import เพื่อประสิทธิภาพ
// =============================================================================

export const ectReport69Data = buildReport();
