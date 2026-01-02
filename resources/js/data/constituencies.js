// ข้อมูลเขตเลือกตั้ง 400 เขต ของประเทศไทย (ตามข้อมูล กกต. 2566)
// แบ่งตามจังหวัด

import { provinces } from './provinces';

// ฟังก์ชันสร้างข้อมูลเขตเลือกตั้งสำหรับแต่ละจังหวัด
const generateConstituencies = () => {
    const constituencies = [];
    let globalId = 1;

    provinces.forEach(province => {
        for (let i = 1; i <= province.constituencies; i++) {
            constituencies.push({
                id: globalId++,
                province_code: province.code,
                province_id: province.id,
                province_name_th: province.name_th,
                number: i,
                name_th: `เขต ${i}`,
                name_en: `Constituency ${i}`,
                // ข้อมูลโดยประมาณ (สามารถปรับเปลี่ยนได้ตามข้อมูลจริงจาก กกต.)
                eligible_voters: Math.round(province.population / province.constituencies * (0.9 + Math.random() * 0.2)),
                polling_stations: Math.floor(50 + Math.random() * 100),
            });
        }
    });

    return constituencies;
};

export const constituencies = generateConstituencies();

// ข้อมูลเขตเลือกตั้งรายละเอียดสำหรับ กทม. (33 เขต)
export const bangkokConstituencies = [
    { number: 1, districts: ['พระนคร', 'ป้อมปราบศัตรูพ่าย', 'สัมพันธวงศ์', 'ดุสิต (บางส่วน)'] },
    { number: 2, districts: ['ดุสิต (บางส่วน)', 'ราชเทวี'] },
    { number: 3, districts: ['พญาไท'] },
    { number: 4, districts: ['จตุจักร (บางส่วน)'] },
    { number: 5, districts: ['จตุจักร (บางส่วน)', 'บางซื่อ'] },
    { number: 6, districts: ['บางเขน (บางส่วน)'] },
    { number: 7, districts: ['บางเขน (บางส่วน)', 'สายไหม (บางส่วน)'] },
    { number: 8, districts: ['สายไหม (บางส่วน)', 'คลองสามวา (บางส่วน)'] },
    { number: 9, districts: ['คลองสามวา (บางส่วน)', 'มีนบุรี'] },
    { number: 10, districts: ['หนองจอก (บางส่วน)'] },
    { number: 11, districts: ['หนองจอก (บางส่วน)', 'ลาดกระบัง (บางส่วน)'] },
    { number: 12, districts: ['ลาดกระบัง (บางส่วน)'] },
    { number: 13, districts: ['ประเวศ (บางส่วน)', 'สวนหลวง (บางส่วน)'] },
    { number: 14, districts: ['ประเวศ (บางส่วน)', 'บางนา'] },
    { number: 15, districts: ['คลองเตย', 'วัฒนา'] },
    { number: 16, districts: ['ปทุมวัน', 'บางรัก'] },
    { number: 17, districts: ['สาทร', 'ยานนาวา (บางส่วน)'] },
    { number: 18, districts: ['ยานนาวา (บางส่วน)', 'บางคอแหลม'] },
    { number: 19, districts: ['พระโขนง', 'สวนหลวง (บางส่วน)'] },
    { number: 20, districts: ['ห้วยขวาง', 'ดินแดง'] },
    { number: 21, districts: ['วังทองหลาง', 'บึงกุ่ม (บางส่วน)'] },
    { number: 22, districts: ['บึงกุ่ม (บางส่วน)', 'คันนายาว'] },
    { number: 23, districts: ['ลาดพร้าว', 'สะพานสูง'] },
    { number: 24, districts: ['บางกะปิ'] },
    { number: 25, districts: ['ธนบุรี', 'คลองสาน'] },
    { number: 26, districts: ['บางกอกใหญ่', 'บางกอกน้อย (บางส่วน)'] },
    { number: 27, districts: ['บางกอกน้อย (บางส่วน)', 'บางพลัด'] },
    { number: 28, districts: ['ตลิ่งชัน', 'ทวีวัฒนา'] },
    { number: 29, districts: ['ภาษีเจริญ', 'หนองแขม (บางส่วน)'] },
    { number: 30, districts: ['หนองแขม (บางส่วน)', 'บางแค'] },
    { number: 31, districts: ['จอมทอง', 'ราษฎร์บูรณะ'] },
    { number: 32, districts: ['บางขุนเทียน (บางส่วน)', 'บางบอน'] },
    { number: 33, districts: ['บางขุนเทียน (บางส่วน)', 'ทุ่งครุ'] },
];

// ข้อมูลเขตเลือกตั้ง เชียงใหม่ (10 เขต)
export const chiangmaiConstituencies = [
    { number: 1, districts: ['เมืองเชียงใหม่ (บางส่วน)'] },
    { number: 2, districts: ['เมืองเชียงใหม่ (บางส่วน)', 'สารภี'] },
    { number: 3, districts: ['หางดง', 'สันป่าตอง (บางส่วน)'] },
    { number: 4, districts: ['สันป่าตอง (บางส่วน)', 'จอมทอง', 'ดอยหล่อ', 'แม่แจ่ม'] },
    { number: 5, districts: ['ฮอด', 'อมก๋อย', 'ดอยเต่า'] },
    { number: 6, districts: ['สันกำแพง', 'แม่ออน'] },
    { number: 7, districts: ['สันทราย'] },
    { number: 8, districts: ['แม่ริม', 'แม่แตง (บางส่วน)'] },
    { number: 9, districts: ['พร้าว', 'เชียงดาว', 'เวียงแหง'] },
    { number: 10, districts: ['ฝาง', 'แม่อาย', 'ไชยปราการ'] },
];

// ข้อมูลเขตเลือกตั้ง นครราชสีมา (16 เขต)
export const koratConstituencies = [
    { number: 1, districts: ['เมืองนครราชสีมา (บางส่วน)'] },
    { number: 2, districts: ['เมืองนครราชสีมา (บางส่วน)'] },
    { number: 3, districts: ['โนนสูง', 'ขามสะแกแสง'] },
    { number: 4, districts: ['บัวใหญ่', 'แก้งสนามนาง', 'บ้านเหลื่อม'] },
    { number: 5, districts: ['ประทาย', 'พิมาย (บางส่วน)', 'ลำทะเมนชัย'] },
    { number: 6, districts: ['พิมาย (บางส่วน)', 'ชุมพวง', 'เมืองยาง'] },
    { number: 7, districts: ['คง', 'บัวลาย', 'สีดา'] },
    { number: 8, districts: ['โนนไทย', 'พระทองคำ', 'ด่านขุนทด (บางส่วน)'] },
    { number: 9, districts: ['ด่านขุนทด (บางส่วน)', 'เทพารักษ์'] },
    { number: 10, districts: ['สีคิ้ว', 'สูงเนิน (บางส่วน)'] },
    { number: 11, districts: ['สูงเนิน (บางส่วน)', 'ขามทะเลสอ', 'โชคชัย (บางส่วน)'] },
    { number: 12, districts: ['โชคชัย (บางส่วน)', 'หนองบุญมาก'] },
    { number: 13, districts: ['ครบุรี', 'เสิงสาง'] },
    { number: 14, districts: ['ปักธงชัย', 'วังน้ำเขียว'] },
    { number: 15, districts: ['ปากช่อง (บางส่วน)'] },
    { number: 16, districts: ['ปากช่อง (บางส่วน)', 'หมวกเหล็ก'] },
];

// ข้อมูลเขตเลือกตั้ง ขอนแก่น (11 เขต)
export const khonkaenConstituencies = [
    { number: 1, districts: ['เมืองขอนแก่น (บางส่วน)'] },
    { number: 2, districts: ['เมืองขอนแก่น (บางส่วน)'] },
    { number: 3, districts: ['เมืองขอนแก่น (บางส่วน)', 'บ้านฝาง'] },
    { number: 4, districts: ['น้ำพอง (บางส่วน)'] },
    { number: 5, districts: ['น้ำพอง (บางส่วน)', 'อุบลรัตน์', 'กระนวน'] },
    { number: 6, districts: ['เขาสวนกวาง', 'ซำสูง', 'โคกโพธิ์ไชย', 'โนนศิลา'] },
    { number: 7, districts: ['มัญจาคีรี', 'แวงใหญ่', 'แวงน้อย'] },
    { number: 8, districts: ['ชนบท', 'บ้านไผ่', 'เปือยน้อย'] },
    { number: 9, districts: ['พล', 'หนองสองห้อง'] },
    { number: 10, districts: ['ชุมแพ', 'สีชมพู', 'ภูผาม่าน'] },
    { number: 11, districts: ['ภูเวียง', 'หนองนาคำ', 'เวียงเก่า'] },
];

// ฟังก์ชันหาเขตเลือกตั้งตาม province_code
export const getConstituenciesByProvinceCode = (code) => constituencies.filter(c => c.province_code === code);

// ฟังก์ชันหาเขตเลือกตั้งตาม province_id
export const getConstituenciesByProvinceId = (provinceId) => constituencies.filter(c => c.province_id === provinceId);

// ฟังก์ชันหาเขตเลือกตั้งจาก id
export const getConstituencyById = (id) => constituencies.find(c => c.id === id);

// คำนวณจำนวนเขตทั้งหมด
export const totalConstituencies = constituencies.length;

export default {
    constituencies,
    bangkokConstituencies,
    chiangmaiConstituencies,
    koratConstituencies,
    khonkaenConstituencies,
    getConstituenciesByProvinceCode,
    getConstituenciesByProvinceId,
    getConstituencyById,
    totalConstituencies,
};
