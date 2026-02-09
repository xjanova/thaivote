// ข้อมูลจำลอง ECT Report 69 (รายงานผลการเลือกตั้ง แบบ 69)
// การเลือกตั้ง ส.ส. 2569 วันที่ 8 กุมภาพันธ์ 2569
// ข้อมูลครอบคลุม 77 จังหวัด 400 เขตเลือกตั้ง
// มีข้อมูลผิดปกติบางเขตเพื่อทดสอบระบบตรวจจับ

// Deterministic pseudo-random number generator (seeded)
function seededRandom(seed) {
    let s = seed;
    return () => {
        s = (s * 16807 + 0) % 2147483647;
        return (s - 1) / 2147483646;
    };
}

// พรรคการเมืองหลัก
const PARTIES = [
    { name: 'พรรคประชาชน', color: '#FF6B00', abbr: 'PP' },
    { name: 'พรรคเพื่อไทย', color: '#E31E25', abbr: 'PT' },
    { name: 'พรรคภูมิใจไทย', color: '#0066B3', abbr: 'BJT' },
    { name: 'พรรคพลังประชารัฐ', color: '#1E3A8A', abbr: 'PPRP' },
    { name: 'พรรครวมไทยสร้างชาติ', color: '#6B21A8', abbr: 'UTN' },
    { name: 'พรรคประชาธิปัตย์', color: '#00AEEF', abbr: 'DP' },
];

// ชื่อผู้สมัครจำลองตามพรรค (ใช้สร้างแบบ deterministic)
const FIRST_NAMES = [
    'สมชาย',
    'สมศักดิ์',
    'สมหญิง',
    'วิชัย',
    'ประยุทธ์',
    'อนุทิน',
    'จุรินทร์',
    'พิธา',
    'ศิริกัญญา',
    'ชัชชาติ',
    'สุดารัตน์',
    'กรณ์',
    'อภิสิทธิ์',
    'ธนาธร',
    'พรรณิการ์',
    'ปิยบุตร',
    'ณัฐวุฒิ',
    'จตุพร',
    'สุเทพ',
    'อุดม',
    'มนตรี',
    'วรรณา',
    'ประสิทธิ์',
    'สำราญ',
    'บุญเลิศ',
    'นิรันดร์',
    'พิชิต',
    'สมาน',
    'ทวีศักดิ์',
    'ราเชนทร์',
];

const LAST_NAMES = [
    'สุขสมบูรณ์',
    'ดีงาม',
    'ใจเย็น',
    'แสงทอง',
    'พรมมา',
    'ศรีสวัสดิ์',
    'ทรงธรรม',
    'สิริมงคล',
    'วัฒนา',
    'เจริญสุข',
    'รุ่งเรือง',
    'มงคลชัย',
    'สว่างวงศ์',
    'ชูเกียรติ',
    'บุญมา',
    'พิพัฒน์',
    'จันทร์เพ็ญ',
    'ทิพย์สุคนธ์',
    'สมานมิตร',
    'รัตนา',
];

// ข้อมูล 77 จังหวัด
const PROVINCE_DATA = [
    {
        id: 1,
        name_th: 'เชียงใหม่',
        name_en: 'Chiang Mai',
        region: 'north',
        constituencies: 10,
        pop: 1779954,
    },
    {
        id: 2,
        name_th: 'ลำพูน',
        name_en: 'Lamphun',
        region: 'north',
        constituencies: 2,
        pop: 404560,
    },
    {
        id: 3,
        name_th: 'ลำปาง',
        name_en: 'Lampang',
        region: 'north',
        constituencies: 4,
        pop: 729658,
    },
    {
        id: 4,
        name_th: 'อุตรดิตถ์',
        name_en: 'Uttaradit',
        region: 'north',
        constituencies: 3,
        pop: 454260,
    },
    { id: 5, name_th: 'แพร่', name_en: 'Phrae', region: 'north', constituencies: 3, pop: 436103 },
    { id: 6, name_th: 'น่าน', name_en: 'Nan', region: 'north', constituencies: 3, pop: 479431 },
    { id: 7, name_th: 'พะเยา', name_en: 'Phayao', region: 'north', constituencies: 3, pop: 471081 },
    {
        id: 8,
        name_th: 'เชียงราย',
        name_en: 'Chiang Rai',
        region: 'north',
        constituencies: 7,
        pop: 1285817,
    },
    {
        id: 9,
        name_th: 'แม่ฮ่องสอน',
        name_en: 'Mae Hong Son',
        region: 'north',
        constituencies: 1,
        pop: 285586,
    },
    {
        id: 10,
        name_th: 'นครราชสีมา',
        name_en: 'Nakhon Ratchasima',
        region: 'northeast',
        constituencies: 16,
        pop: 2634154,
    },
    {
        id: 11,
        name_th: 'บุรีรัมย์',
        name_en: 'Buri Ram',
        region: 'northeast',
        constituencies: 10,
        pop: 1595065,
    },
    {
        id: 12,
        name_th: 'สุรินทร์',
        name_en: 'Surin',
        region: 'northeast',
        constituencies: 8,
        pop: 1388359,
    },
    {
        id: 13,
        name_th: 'ศรีสะเกษ',
        name_en: 'Si Sa Ket',
        region: 'northeast',
        constituencies: 9,
        pop: 1463615,
    },
    {
        id: 14,
        name_th: 'อุบลราชธานี',
        name_en: 'Ubon Ratchathani',
        region: 'northeast',
        constituencies: 11,
        pop: 1878146,
    },
    {
        id: 15,
        name_th: 'ยโสธร',
        name_en: 'Yasothon',
        region: 'northeast',
        constituencies: 3,
        pop: 536018,
    },
    {
        id: 16,
        name_th: 'ชัยภูมิ',
        name_en: 'Chaiyaphum',
        region: 'northeast',
        constituencies: 7,
        pop: 1127423,
    },
    {
        id: 17,
        name_th: 'อำนาจเจริญ',
        name_en: 'Amnat Charoen',
        region: 'northeast',
        constituencies: 2,
        pop: 375689,
    },
    {
        id: 18,
        name_th: 'บึงกาฬ',
        name_en: 'Bueng Kan',
        region: 'northeast',
        constituencies: 2,
        pop: 422091,
    },
    {
        id: 19,
        name_th: 'หนองบัวลำภู',
        name_en: 'Nong Bua Lam Phu',
        region: 'northeast',
        constituencies: 3,
        pop: 509834,
    },
    {
        id: 20,
        name_th: 'ขอนแก่น',
        name_en: 'Khon Kaen',
        region: 'northeast',
        constituencies: 11,
        pop: 1792905,
    },
    {
        id: 21,
        name_th: 'อุดรธานี',
        name_en: 'Udon Thani',
        region: 'northeast',
        constituencies: 10,
        pop: 1570500,
    },
    {
        id: 22,
        name_th: 'เลย',
        name_en: 'Loei',
        region: 'northeast',
        constituencies: 4,
        pop: 637624,
    },
    {
        id: 23,
        name_th: 'หนองคาย',
        name_en: 'Nong Khai',
        region: 'northeast',
        constituencies: 3,
        pop: 517260,
    },
    {
        id: 24,
        name_th: 'มหาสารคาม',
        name_en: 'Maha Sarakham',
        region: 'northeast',
        constituencies: 6,
        pop: 960359,
    },
    {
        id: 25,
        name_th: 'ร้อยเอ็ด',
        name_en: 'Roi Et',
        region: 'northeast',
        constituencies: 8,
        pop: 1306104,
    },
    {
        id: 26,
        name_th: 'กาฬสินธุ์',
        name_en: 'Kalasin',
        region: 'northeast',
        constituencies: 6,
        pop: 983720,
    },
    {
        id: 27,
        name_th: 'สกลนคร',
        name_en: 'Sakon Nakhon',
        region: 'northeast',
        constituencies: 7,
        pop: 1148052,
    },
    {
        id: 28,
        name_th: 'นครพนม',
        name_en: 'Nakhon Phanom',
        region: 'northeast',
        constituencies: 4,
        pop: 714740,
    },
    {
        id: 29,
        name_th: 'มุกดาหาร',
        name_en: 'Mukdahan',
        region: 'northeast',
        constituencies: 2,
        pop: 352837,
    },
    {
        id: 30,
        name_th: 'กรุงเทพมหานคร',
        name_en: 'Bangkok',
        region: 'central',
        constituencies: 33,
        pop: 5494910,
    },
    {
        id: 31,
        name_th: 'สมุทรปราการ',
        name_en: 'Samut Prakan',
        region: 'central',
        constituencies: 8,
        pop: 1349263,
    },
    {
        id: 32,
        name_th: 'นนทบุรี',
        name_en: 'Nonthaburi',
        region: 'central',
        constituencies: 8,
        pop: 1278540,
    },
    {
        id: 33,
        name_th: 'ปทุมธานี',
        name_en: 'Pathum Thani',
        region: 'central',
        constituencies: 7,
        pop: 1176412,
    },
    {
        id: 34,
        name_th: 'พระนครศรีอยุธยา',
        name_en: 'Phra Nakhon Si Ayutthaya',
        region: 'central',
        constituencies: 5,
        pop: 815384,
    },
    {
        id: 35,
        name_th: 'อ่างทอง',
        name_en: 'Ang Thong',
        region: 'central',
        constituencies: 2,
        pop: 280115,
    },
    {
        id: 36,
        name_th: 'ลพบุรี',
        name_en: 'Lop Buri',
        region: 'central',
        constituencies: 4,
        pop: 753828,
    },
    {
        id: 37,
        name_th: 'สิงห์บุรี',
        name_en: 'Sing Buri',
        region: 'central',
        constituencies: 1,
        pop: 206804,
    },
    {
        id: 38,
        name_th: 'ชัยนาท',
        name_en: 'Chai Nat',
        region: 'central',
        constituencies: 2,
        pop: 326949,
    },
    {
        id: 39,
        name_th: 'สระบุรี',
        name_en: 'Saraburi',
        region: 'central',
        constituencies: 4,
        pop: 645641,
    },
    {
        id: 40,
        name_th: 'นครสวรรค์',
        name_en: 'Nakhon Sawan',
        region: 'central',
        constituencies: 6,
        pop: 1059887,
    },
    {
        id: 41,
        name_th: 'อุทัยธานี',
        name_en: 'Uthai Thani',
        region: 'central',
        constituencies: 2,
        pop: 328292,
    },
    {
        id: 42,
        name_th: 'กำแพงเพชร',
        name_en: 'Kamphaeng Phet',
        region: 'central',
        constituencies: 4,
        pop: 725853,
    },
    { id: 43, name_th: 'ตาก', name_en: 'Tak', region: 'central', constituencies: 3, pop: 664450 },
    {
        id: 44,
        name_th: 'สุโขทัย',
        name_en: 'Sukhothai',
        region: 'central',
        constituencies: 4,
        pop: 597207,
    },
    {
        id: 45,
        name_th: 'พิษณุโลก',
        name_en: 'Phitsanulok',
        region: 'central',
        constituencies: 5,
        pop: 865368,
    },
    {
        id: 46,
        name_th: 'พิจิตร',
        name_en: 'Phichit',
        region: 'central',
        constituencies: 3,
        pop: 539529,
    },
    {
        id: 47,
        name_th: 'เพชรบูรณ์',
        name_en: 'Phetchabun',
        region: 'central',
        constituencies: 6,
        pop: 993693,
    },
    {
        id: 48,
        name_th: 'สุพรรณบุรี',
        name_en: 'Suphan Buri',
        region: 'central',
        constituencies: 5,
        pop: 842123,
    },
    {
        id: 49,
        name_th: 'นครปฐม',
        name_en: 'Nakhon Pathom',
        region: 'central',
        constituencies: 6,
        pop: 926732,
    },
    {
        id: 50,
        name_th: 'สมุทรสาคร',
        name_en: 'Samut Sakhon',
        region: 'central',
        constituencies: 3,
        pop: 579113,
    },
    {
        id: 51,
        name_th: 'สมุทรสงคราม',
        name_en: 'Samut Songkhram',
        region: 'central',
        constituencies: 1,
        pop: 193729,
    },
    {
        id: 52,
        name_th: 'ชลบุรี',
        name_en: 'Chon Buri',
        region: 'east',
        constituencies: 9,
        pop: 1558301,
    },
    { id: 53, name_th: 'ระยอง', name_en: 'Rayong', region: 'east', constituencies: 4, pop: 742323 },
    {
        id: 54,
        name_th: 'จันทบุรี',
        name_en: 'Chanthaburi',
        region: 'east',
        constituencies: 3,
        pop: 540221,
    },
    { id: 55, name_th: 'ตราด', name_en: 'Trat', region: 'east', constituencies: 1, pop: 229235 },
    {
        id: 56,
        name_th: 'ฉะเชิงเทรา',
        name_en: 'Chachoengsao',
        region: 'east',
        constituencies: 4,
        pop: 716689,
    },
    {
        id: 57,
        name_th: 'ปราจีนบุรี',
        name_en: 'Prachin Buri',
        region: 'east',
        constituencies: 3,
        pop: 487026,
    },
    {
        id: 58,
        name_th: 'นครนายก',
        name_en: 'Nakhon Nayok',
        region: 'east',
        constituencies: 2,
        pop: 260654,
    },
    {
        id: 59,
        name_th: 'สระแก้ว',
        name_en: 'Sa Kaeo',
        region: 'east',
        constituencies: 3,
        pop: 564615,
    },
    {
        id: 60,
        name_th: 'ราชบุรี',
        name_en: 'Ratchaburi',
        region: 'west',
        constituencies: 5,
        pop: 870913,
    },
    {
        id: 61,
        name_th: 'กาญจนบุรี',
        name_en: 'Kanchanaburi',
        region: 'west',
        constituencies: 5,
        pop: 898158,
    },
    {
        id: 62,
        name_th: 'เพชรบุรี',
        name_en: 'Phetchaburi',
        region: 'west',
        constituencies: 3,
        pop: 483448,
    },
    {
        id: 63,
        name_th: 'ประจวบคีรีขันธ์',
        name_en: 'Prachuap Khiri Khan',
        region: 'west',
        constituencies: 3,
        pop: 547338,
    },
    {
        id: 64,
        name_th: 'นครศรีธรรมราช',
        name_en: 'Nakhon Si Thammarat',
        region: 'south',
        constituencies: 10,
        pop: 1563637,
    },
    {
        id: 65,
        name_th: 'กระบี่',
        name_en: 'Krabi',
        region: 'south',
        constituencies: 3,
        pop: 479541,
    },
    {
        id: 66,
        name_th: 'พังงา',
        name_en: 'Phang Nga',
        region: 'south',
        constituencies: 2,
        pop: 270320,
    },
    {
        id: 67,
        name_th: 'ภูเก็ต',
        name_en: 'Phuket',
        region: 'south',
        constituencies: 3,
        pop: 418864,
    },
    {
        id: 68,
        name_th: 'สุราษฎร์ธานี',
        name_en: 'Surat Thani',
        region: 'south',
        constituencies: 6,
        pop: 1067846,
    },
    {
        id: 69,
        name_th: 'ระนอง',
        name_en: 'Ranong',
        region: 'south',
        constituencies: 1,
        pop: 192887,
    },
    {
        id: 70,
        name_th: 'ชุมพร',
        name_en: 'Chumphon',
        region: 'south',
        constituencies: 3,
        pop: 509650,
    },
    {
        id: 71,
        name_th: 'สงขลา',
        name_en: 'Songkhla',
        region: 'south',
        constituencies: 9,
        pop: 1439713,
    },
    { id: 72, name_th: 'สตูล', name_en: 'Satun', region: 'south', constituencies: 2, pop: 320294 },
    { id: 73, name_th: 'ตรัง', name_en: 'Trang', region: 'south', constituencies: 4, pop: 641510 },
    {
        id: 74,
        name_th: 'พัทลุง',
        name_en: 'Phatthalung',
        region: 'south',
        constituencies: 3,
        pop: 525295,
    },
    {
        id: 75,
        name_th: 'ปัตตานี',
        name_en: 'Pattani',
        region: 'south',
        constituencies: 4,
        pop: 727541,
    },
    { id: 76, name_th: 'ยะลา', name_en: 'Yala', region: 'south', constituencies: 3, pop: 533557 },
    {
        id: 77,
        name_th: 'นราธิวาส',
        name_en: 'Narathiwat',
        region: 'south',
        constituencies: 4,
        pop: 813445,
    },
];

// สร้างชื่อผู้สมัครแบบ deterministic
function generateCandidateName(seed) {
    const rng = seededRandom(seed);
    const fi = Math.floor(rng() * FIRST_NAMES.length);
    const li = Math.floor(rng() * LAST_NAMES.length);
    return `${FIRST_NAMES[fi]} ${LAST_NAMES[li]}`;
}

// สร้างข้อมูลเขตเลือกตั้งปกติ
function generateNormalConstituency(provinceId, constNum, eligibleVoters) {
    const seed = provinceId * 1000 + constNum;
    const rng = seededRandom(seed);

    const totalStations = Math.floor(80 + rng() * 120);
    const turnoutRate = 0.6 + rng() * 0.2; // 60-80%
    const totalVoters = Math.floor(eligibleVoters * turnoutRate);
    const badBallotRate = 0.01 + rng() * 0.03; // 1-4%
    const badBallots = Math.floor(totalVoters * badBallotRate);
    const goodBallots = totalVoters - badBallots;
    const noVoteRate = 0.02 + rng() * 0.03; // 2-5%
    const noVote = Math.floor(goodBallots * noVoteRate);
    const availableVotes = goodBallots - noVote;

    // เลือกจำนวนผู้สมัคร 3-5 คน
    const numCandidates = 3 + Math.floor(rng() * 3);

    // กระจายคะแนนให้ผู้สมัคร (ผู้ชนะได้ ~35-55%)
    const winnerShare = 0.35 + rng() * 0.2;
    const winnerVotes = Math.floor(availableVotes * winnerShare);
    let remaining = availableVotes - winnerVotes;

    // เลือกพรรคแบบ deterministic (สลับตามภาค/จังหวัด)
    const partyOffset = Math.floor(rng() * PARTIES.length);

    const candidates = [];
    for (let i = 0; i < numCandidates; i++) {
        const party = PARTIES[(partyOffset + i) % PARTIES.length];
        let votes;
        if (i === 0) {
            votes = winnerVotes;
        } else if (i < numCandidates - 1) {
            const share = 0.1 + rng() * 0.3;
            votes = Math.floor(remaining * share);
            remaining -= votes;
        } else {
            votes = remaining;
        }

        candidates.push({
            number: i + 1,
            name: generateCandidateName(seed * 100 + i),
            party: party.name,
            party_color: party.color,
            votes: Math.max(votes, 50),
            is_winner: i === 0,
        });
    }

    return {
        number: constNum,
        total_stations: totalStations,
        counted_stations: totalStations,
        eligible_voters: eligibleVoters,
        total_voters: totalVoters,
        good_ballots: goodBallots,
        bad_ballots: badBallots,
        no_vote: noVote,
        candidates,
    };
}

// สร้างเขตที่มีความผิดปกติ (hardcoded)
function getAnomalousConstituencies() {
    return {
        // บุรีรัมย์ เขต 3: คะแนนผู้ชนะ > บัตรดี
        '11_3': {
            number: 3,
            total_stations: 145,
            counted_stations: 145,
            eligible_voters: 68000,
            total_voters: 47600,
            good_ballots: 42000,
            bad_ballots: 5600,
            no_vote: 1500,
            candidates: [
                {
                    number: 1,
                    name: 'สมชาย ทรงธรรม',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 45000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'วิชัย แสงทอง',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 8500,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'สมศักดิ์ ดีงาม',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 5200,
                    is_winner: false,
                },
            ],
        },
        // กรุงเทพฯ เขต 15: ผู้มาใช้สิทธิ > ผู้มีสิทธิ
        '30_15': {
            number: 15,
            total_stations: 160,
            counted_stations: 160,
            eligible_voters: 85000,
            total_voters: 88500,
            good_ballots: 86000,
            bad_ballots: 2500,
            no_vote: 2800,
            candidates: [
                {
                    number: 1,
                    name: 'พิธา สิริมงคล',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 42000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'สุดารัตน์ รุ่งเรือง',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 28500,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'กรณ์ มงคลชัย',
                    party: 'พรรคประชาธิปัตย์',
                    party_color: '#00AEEF',
                    votes: 9700,
                    is_winner: false,
                },
                {
                    number: 4,
                    name: 'อุดม พิพัฒน์',
                    party: 'พรรครวมไทยสร้างชาติ',
                    party_color: '#6B21A8',
                    votes: 3000,
                    is_winner: false,
                },
            ],
        },
        // ขอนแก่น เขต 5: บัตรดี + บัตรเสีย ≠ ผู้มาใช้สิทธิ
        '20_5': {
            number: 5,
            total_stations: 130,
            counted_stations: 130,
            eligible_voters: 72000,
            total_voters: 50400,
            good_ballots: 49500,
            bad_ballots: 3200,
            no_vote: 1800,
            candidates: [
                {
                    number: 1,
                    name: 'ณัฐวุฒิ เจริญสุข',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 25000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'ทวีศักดิ์ วัฒนา',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 15500,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'ราเชนทร์ บุญมา',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 7200,
                    is_winner: false,
                },
            ],
        },
        // นครศรีธรรมราช เขต 7: ผลรวมคะแนน + ไม่ประสงค์ > บัตรดี
        '64_7': {
            number: 7,
            total_stations: 110,
            counted_stations: 110,
            eligible_voters: 70000,
            total_voters: 52500,
            good_ballots: 50800,
            bad_ballots: 1700,
            no_vote: 2200,
            candidates: [
                {
                    number: 1,
                    name: 'ประสิทธิ์ ชูเกียรติ',
                    party: 'พรรคประชาธิปัตย์',
                    party_color: '#00AEEF',
                    votes: 28000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'สำราญ สมานมิตร',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 15800,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'มนตรี รัตนา',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 8500,
                    is_winner: false,
                },
            ],
        },
        // สงขลา เขต 4: อัตราผู้มาใช้สิทธิสูงผิดปกติ 99.5%
        '71_4': {
            number: 4,
            total_stations: 125,
            counted_stations: 125,
            eligible_voters: 65000,
            total_voters: 64675,
            good_ballots: 62800,
            bad_ballots: 1875,
            no_vote: 2100,
            candidates: [
                {
                    number: 1,
                    name: 'บุญเลิศ สว่างวงศ์',
                    party: 'พรรคประชาธิปัตย์',
                    party_color: '#00AEEF',
                    votes: 32000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'นิรันดร์ จันทร์เพ็ญ',
                    party: 'พรรคภูมิใจไทย',
                    party_color: '#0066B3',
                    votes: 18500,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'พิชิต ทิพย์สุคนธ์',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 10200,
                    is_winner: false,
                },
            ],
        },
        // ตาก เขต 2: อัตราบัตรเสียสูงผิดปกติ 15%
        '43_2': {
            number: 2,
            total_stations: 95,
            counted_stations: 95,
            eligible_voters: 60000,
            total_voters: 42000,
            good_ballots: 35700,
            bad_ballots: 6300,
            no_vote: 1200,
            candidates: [
                {
                    number: 1,
                    name: 'สมาน สุขสมบูรณ์',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 18000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'วรรณา ใจเย็น',
                    party: 'พรรคพลังประชารัฐ',
                    party_color: '#1E3A8A',
                    votes: 12300,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'ประยุทธ์ พรมมา',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 4200,
                    is_winner: false,
                },
            ],
        },
        // ปัตตานี เขต 2: อัตราผู้มาใช้สิทธิต่ำ 25%
        '75_2': {
            number: 2,
            total_stations: 100,
            counted_stations: 100,
            eligible_voters: 80000,
            total_voters: 20000,
            good_ballots: 19400,
            bad_ballots: 600,
            no_vote: 800,
            candidates: [
                {
                    number: 1,
                    name: 'มุฮัมมัด ยูซุฟ',
                    party: 'พรรคประชาชาติ',
                    party_color: '#2D8B4E',
                    votes: 9800,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'อิบรอฮีม สาและ',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 5500,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'อับดุลเลาะ มะ',
                    party: 'พรรคประชาธิปัตย์',
                    party_color: '#00AEEF',
                    votes: 3300,
                    is_winner: false,
                },
            ],
        },
        // สุพรรณบุรี เขต 3: ผู้สมัครได้ 0 คะแนน
        '48_3': {
            number: 3,
            total_stations: 115,
            counted_stations: 115,
            eligible_voters: 72000,
            total_voters: 50400,
            good_ballots: 48900,
            bad_ballots: 1500,
            no_vote: 1600,
            candidates: [
                {
                    number: 1,
                    name: 'ชัชชาติ ศรีสวัสดิ์',
                    party: 'พรรคชาติไทยพัฒนา',
                    party_color: '#8B4513',
                    votes: 28000,
                    is_winner: true,
                },
                {
                    number: 2,
                    name: 'ธนาธร สิริมงคล',
                    party: 'พรรคประชาชน',
                    party_color: '#FF6B00',
                    votes: 12500,
                    is_winner: false,
                },
                {
                    number: 3,
                    name: 'พรรณิการ์ วัฒนา',
                    party: 'พรรคเพื่อไทย',
                    party_color: '#E31E25',
                    votes: 6800,
                    is_winner: false,
                },
                {
                    number: 4,
                    name: 'นายทดสอบ ไม่มีคะแนน',
                    party: 'พรรครวมไทยสร้างชาติ',
                    party_color: '#6B21A8',
                    votes: 0,
                    is_winner: false,
                },
            ],
        },
    };
}

// สร้างข้อมูลทั้งหมด
function buildECTReport69Data() {
    const anomalousData = getAnomalousConstituencies();
    const provinces = [];

    for (const p of PROVINCE_DATA) {
        const constituencies = [];
        const eligiblePerConst = Math.floor((p.pop * 0.75) / p.constituencies);

        for (let c = 1; c <= p.constituencies; c++) {
            const key = `${p.id}_${c}`;
            if (anomalousData[key]) {
                constituencies.push(anomalousData[key]);
            } else {
                // ปรับ eligible voters ให้แต่ละเขตมีความแตกต่าง
                const seed = p.id * 1000 + c;
                const rng = seededRandom(seed + 999);
                const variation = 0.85 + rng() * 0.3;
                const eligible = Math.floor(eligiblePerConst * variation);
                constituencies.push(generateNormalConstituency(p.id, c, eligible));
            }
        }

        // คำนวณสรุปจังหวัด
        const summary = {
            eligible_voters: 0,
            total_voters: 0,
            good_ballots: 0,
            bad_ballots: 0,
            no_vote: 0,
        };
        for (const c of constituencies) {
            summary.eligible_voters += c.eligible_voters;
            summary.total_voters += c.total_voters;
            summary.good_ballots += c.good_ballots;
            summary.bad_ballots += c.bad_ballots;
            summary.no_vote += c.no_vote;
        }

        provinces.push({
            id: p.id,
            name_th: p.name_th,
            name_en: p.name_en,
            region: p.region,
            total_constituencies: p.constituencies,
            counted: p.constituencies,
            summary,
            constituencies,
        });
    }

    // คำนวณสรุประดับชาติ
    const nationalSummary = {
        eligible_voters: 0,
        total_voters: 0,
        good_ballots: 0,
        bad_ballots: 0,
        no_vote: 0,
    };
    for (const p of provinces) {
        nationalSummary.eligible_voters += p.summary.eligible_voters;
        nationalSummary.total_voters += p.summary.total_voters;
        nationalSummary.good_ballots += p.summary.good_ballots;
        nationalSummary.bad_ballots += p.summary.bad_ballots;
        nationalSummary.no_vote += p.summary.no_vote;
    }

    return {
        election_name: 'การเลือกตั้ง ส.ส. 2569',
        election_date: '2026-02-08',
        report_time: '2026-02-08T23:45:00+07:00',
        total_constituencies: 400,
        counted_constituencies: 400,
        national_summary: nationalSummary,
        provinces,
    };
}

export const ectReport69Data = buildECTReport69Data();
