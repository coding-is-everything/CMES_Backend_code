<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    /**
     * Districts keyed by the state_code they belong to (see StateSeeder).
     */
    protected function districtsByStateCode(): array
    {
        return [
            'AP' => [
                'Alluri Sitharama Raju', 'Anakapalli', 'Ananthapuramu', 'Annamayya', 'Bapatla',
                'Chittoor', 'Dr. B.R. Ambedkar Konaseema', 'East Godavari', 'Eluru', 'Guntur',
                'Kakinada', 'Krishna', 'Kurnool', 'Markapuram', 'Nandyal', 'NTR', 'Palnadu',
                'Parvathipuram Manyam', 'Polavaram', 'Prakasam', 'Sri Potti Sriramulu Nellore',
                'Sri Sathya Sai', 'Srikakulam', 'Tirupati', 'Visakhapatnam', 'Vizianagaram',
                'West Godavari', 'YSR Kadapa',
            ],
            'AR' => [
                'Anjaw', 'Bichom', 'Changlang', 'East Kameng', 'East Siang', 'Kamle', 'Keyi Panyor',
                'Kra Daadi', 'Kurung Kumey', 'Lepa Rada', 'Lohit', 'Longding', 'Lower Dibang Valley',
                'Lower Siang', 'Lower Subansiri', 'Namsai', 'Pakke-Kessang', 'Papum Pare', 'Shi Yomi',
                'Siang', 'Tawang', 'Tirap', 'Upper Dibang Valley', 'Upper Siang', 'Upper Subansiri',
                'West Kameng', 'West Siang',
            ],
            'AS' => [
                'Bajali', 'Baksa', 'Barpeta', 'Biswanath', 'Bongaigaon', 'Cachar', 'Charaideo',
                'Chirang', 'Darrang', 'Dhemaji', 'Dhubri', 'Dibrugarh', 'Dima Hasao', 'Goalpara',
                'Golaghat', 'Hailakandi', 'Hojai', 'Jorhat', 'Kamrup', 'Kamrup Metropolitan',
                'Karbi Anglong', 'Karimganj', 'Kokrajhar', 'Lakhimpur', 'Majuli', 'Morigaon',
                'Nagaon', 'Nalbari', 'Sivasagar', 'Sonitpur', 'South Salmara-Mankachar', 'Tamulpur',
                'Tinsukia', 'Udalguri', 'West Karbi Anglong',
            ],
            'BR' => [
                'Araria', 'Arwal', 'Aurangabad', 'Banka', 'Begusarai', 'Bhagalpur', 'Bhojpur',
                'Buxar', 'Darbhanga', 'East Champaran', 'Gaya', 'Gopalganj', 'Jamui', 'Jehanabad',
                'Kaimur', 'Katihar', 'Khagaria', 'Kishanganj', 'Lakhisarai', 'Madhepura', 'Madhubani',
                'Munger', 'Muzaffarpur', 'Nalanda', 'Nawada', 'Patna', 'Purnia', 'Rohtas', 'Saharsa',
                'Samastipur', 'Saran', 'Sheikhpura', 'Sheohar', 'Sitamarhi', 'Siwan', 'Supaul',
                'Vaishali', 'West Champaran',
            ],
            'CG' => [
                'Balod', 'Baloda Bazar-Bhatapara', 'Balrampur', 'Bastar', 'Bemetara', 'Bijapur',
                'Bilaspur', 'Dantewada', 'Dhamtari', 'Durg', 'Gariaband', 'Gaurela-Pendra-Marwahi',
                'Janjgir-Champa', 'Jashpur', 'Kabirdham', 'Khairagarh-Chhuikhadan-Gandai', 'Kanker',
                'Kondagaon', 'Korba', 'Koriya', 'Mahasamund', 'Manendragarh-Chirmiri-Bharatpur',
                'Mohla-Manpur-Ambagarh Chowki', 'Mungeli', 'Narayanpur', 'Raigarh', 'Raipur',
                'Rajnandgaon', 'Sakti', 'Sarangarh-Bilaigarh', 'Sukma', 'Surajpur', 'Surguja',
            ],
            'GA' => ['Kushavati', 'North Goa', 'South Goa'],
            'GJ' => [
                'Ahmedabad', 'Amreli', 'Anand', 'Aravalli', 'Banaskantha', 'Bharuch', 'Bhavnagar',
                'Botad', 'Chhota Udaipur', 'Dahod', 'Dang', 'Devbhoomi Dwarka', 'Gandhinagar',
                'Gir Somnath', 'Jamnagar', 'Junagadh', 'Kheda', 'Kutch', 'Mahisagar', 'Mehsana',
                'Morbi', 'Narmada', 'Navsari', 'Panchmahal', 'Patan', 'Porbandar', 'Rajkot',
                'Sabarkantha', 'Surat', 'Surendranagar', 'Tapi', 'Vadodara', 'Valsad', 'Vav-Tharad',
            ],
            'HR' => [
                'Ambala', 'Bhiwani', 'Charkhi Dadri', 'Faridabad', 'Fatehabad', 'Gurugram', 'Hansi',
                'Hisar', 'Jhajjar', 'Jind', 'Kaithal', 'Karnal', 'Kurukshetra', 'Mahendragarh', 'Nuh',
                'Palwal', 'Panchkula', 'Panipat', 'Rewari', 'Rohtak', 'Sirsa', 'Sonipat', 'Yamunanagar',
            ],
            'HP' => [
                'Bilaspur', 'Chamba', 'Hamirpur', 'Kangra', 'Kinnaur', 'Kullu', 'Lahaul and Spiti',
                'Mandi', 'Shimla', 'Sirmaur', 'Solan', 'Una',
            ],
            'JH' => [
                'Bokaro', 'Chatra', 'Deoghar', 'Dhanbad', 'Dumka', 'East Singhbhum', 'Garhwa',
                'Giridih', 'Godda', 'Gumla', 'Hazaribagh', 'Jamtara', 'Khunti', 'Koderma', 'Latehar',
                'Lohardaga', 'Pakur', 'Palamu', 'Ramgarh', 'Ranchi', 'Sahibganj', 'Saraikela Kharsawan',
                'Simdega', 'West Singhbhum',
            ],
            'KA' => [
                'Bagalkote', 'Ballari', 'Belagavi', 'Bengaluru Rural', 'Bengaluru Urban', 'Bidar',
                'Chamarajanagar', 'Chikkaballapura', 'Chikkamagaluru', 'Chitradurga',
                'Dakshina Kannada', 'Davanagere', 'Dharwad', 'Gadag', 'Hassan', 'Haveri', 'Kalaburagi',
                'Kodagu', 'Kolar', 'Koppal', 'Mandya', 'Mysuru', 'Raichur', 'Ramanagara', 'Shivamogga',
                'Tumakuru', 'Udupi', 'Uttara Kannada', 'Vijayapura', 'Vijayanagara', 'Yadgir',
            ],
            'KL' => [
                'Alappuzha', 'Ernakulam', 'Idukki', 'Kannur', 'Kasaragod', 'Kollam', 'Kottayam',
                'Kozhikode', 'Malappuram', 'Palakkad', 'Pathanamthitta', 'Thiruvananthapuram',
                'Thrissur', 'Wayanad',
            ],
            'MP' => [
                'Agar Malwa', 'Alirajpur', 'Anuppur', 'Ashoknagar', 'Balaghat', 'Barwani', 'Betul',
                'Bhind', 'Bhopal', 'Burhanpur', 'Chhatarpur', 'Chhindwara', 'Damoh', 'Datia', 'Dewas',
                'Dhar', 'Dindori', 'Guna', 'Gwalior', 'Harda', 'Indore', 'Jabalpur', 'Jhabua', 'Katni',
                'Khandwa', 'Khargone', 'Maihar', 'Mandla', 'Mandsaur', 'Mauganj', 'Morena',
                'Narmadapuram', 'Narsinghpur', 'Neemuch', 'Niwari', 'Pandhurna', 'Panna', 'Raisen',
                'Rajgarh', 'Ratlam', 'Rewa', 'Sagar', 'Satna', 'Sehore', 'Seoni', 'Shahdol',
                'Shajapur', 'Sheopur', 'Shivpuri', 'Sidhi', 'Singrauli', 'Tikamgarh', 'Ujjain',
                'Umaria', 'Vidisha',
            ],
            'MH' => [
                'Ahilyanagar', 'Akola', 'Amravati', 'Beed', 'Bhandara', 'Buldhana',
                'Chhatrapati Sambhajinagar', 'Chandrapur', 'Dharashiv', 'Dhule', 'Gadchiroli',
                'Gondia', 'Hingoli', 'Jalgaon', 'Jalna', 'Kolhapur', 'Latur', 'Mumbai City',
                'Mumbai Suburban', 'Nagpur', 'Nanded', 'Nandurbar', 'Nashik', 'Palghar', 'Parbhani',
                'Pune', 'Raigad', 'Ratnagiri', 'Sangli', 'Satara', 'Sindhudurg', 'Solapur', 'Thane',
                'Wardha', 'Washim', 'Yavatmal',
            ],
            'MN' => [
                'Bishnupur', 'Chandel', 'Churachandpur', 'Imphal East', 'Imphal West', 'Jiribam',
                'Kakching', 'Kamjong', 'Kangpokpi', 'Noney', 'Pherzawl', 'Senapati', 'Tamenglong',
                'Tengnoupal', 'Thoubal', 'Ukhrul',
            ],
            'ML' => [
                'East Garo Hills', 'East Jaintia Hills', 'East Khasi Hills', 'Eastern West Khasi Hills',
                'North Garo Hills', 'Ri Bhoi', 'South Garo Hills', 'South West Garo Hills',
                'South West Khasi Hills', 'West Garo Hills', 'West Jaintia Hills', 'West Khasi Hills',
            ],
            'MZ' => [
                'Aizawl', 'Champhai', 'Hnahthial', 'Khawzawl', 'Kolasib', 'Lawngtlai', 'Lunglei',
                'Mamit', 'Saiha', 'Saitual', 'Serchhip',
            ],
            'NL' => [
                'Chumoukedima', 'Dimapur', 'Kiphire', 'Kohima', 'Longleng', 'Meluri', 'Mokokchung',
                'Mon', 'Niuland', 'Noklak', 'Peren', 'Phek', 'Shamator', 'Tseminyu', 'Tuensang',
                'Wokha', 'Zunheboto',
            ],
            'OD' => [
                'Angul', 'Balangir', 'Balasore', 'Bargarh', 'Bhadrak', 'Boudh', 'Cuttack', 'Debagarh',
                'Dhenkanal', 'Gajapati', 'Ganjam', 'Jagatsinghpur', 'Jajpur', 'Jharsuguda', 'Kalahandi',
                'Kandhamal', 'Kendrapara', 'Kendujhar', 'Khordha', 'Koraput', 'Malkangiri', 'Mayurbhanj',
                'Nabarangpur', 'Nayagarh', 'Nuapada', 'Puri', 'Rayagada', 'Sambalpur', 'Subarnapur',
                'Sundargarh',
            ],
            'PB' => [
                'Amritsar', 'Barnala', 'Bathinda', 'Faridkot', 'Fatehgarh Sahib', 'Fazilka',
                'Firozpur', 'Gurdaspur', 'Hoshiarpur', 'Jalandhar', 'Kapurthala', 'Ludhiana',
                'Malerkotla', 'Mansa', 'Moga', 'Pathankot', 'Patiala', 'Rupnagar',
                'Sahibzada Ajit Singh Nagar', 'Sangrur', 'Shaheed Bhagat Singh Nagar',
                'Sri Muktsar Sahib', 'Tarn Taran',
            ],
            'RJ' => [
                'Ajmer', 'Alwar', 'Balotra', 'Banswara', 'Baran', 'Barmer', 'Beawar', 'Bharatpur',
                'Bhilwara', 'Bikaner', 'Bundi', 'Chittorgarh', 'Churu', 'Dausa', 'Deeg',
                'Didwana-Kuchaman', 'Dholpur', 'Dungarpur', 'Hanumangarh', 'Jaipur', 'Jaisalmer',
                'Jalore', 'Jhalawar', 'Jhunjhunu', 'Jodhpur', 'Karauli', 'Khairthal-Tijara', 'Kota',
                'Kotputli-Behror', 'Nagaur', 'Pali', 'Phalodi', 'Pratapgarh', 'Rajsamand', 'Salumbar',
                'Sawai Madhopur', 'Sikar', 'Sirohi', 'Sri Ganganagar', 'Tonk', 'Udaipur',
            ],
            'SK' => ['Gangtok', 'Gyalshing', 'Mangan', 'Namchi', 'Pakyong', 'Soreng'],
            'TN' => [
                'Ariyalur', 'Chengalpattu', 'Chennai', 'Coimbatore', 'Cuddalore', 'Dharmapuri',
                'Dindigul', 'Erode', 'Kallakurichi', 'Kanchipuram', 'Kanniyakumari', 'Karur',
                'Krishnagiri', 'Madurai', 'Mayiladuthurai', 'Nagapattinam', 'Namakkal', 'Nilgiris',
                'Perambalur', 'Pudukkottai', 'Ramanathapuram', 'Ranipet', 'Salem', 'Sivaganga',
                'Tenkasi', 'Thanjavur', 'Theni', 'Thoothukudi', 'Tiruchirappalli', 'Tirunelveli',
                'Tirupathur', 'Tiruppur', 'Tiruvallur', 'Tiruvannamalai', 'Tiruvarur', 'Vellore',
                'Viluppuram', 'Virudhunagar',
            ],
            'TS' => [
                'Adilabad', 'Bhadradri Kothagudem', 'Hanumakonda', 'Hyderabad', 'Jagtial', 'Jangaon',
                'Jayashankar Bhupalpally', 'Jogulamba Gadwal', 'Kamareddy', 'Karimnagar', 'Khammam',
                'Kumuram Bheem Asifabad', 'Mahabubabad', 'Mahabubnagar', 'Mancherial', 'Medak',
                'Medchal-Malkajgiri', 'Mulugu', 'Nagarkurnool', 'Nalgonda', 'Narayanpet', 'Nirmal',
                'Nizamabad', 'Peddapalli', 'Rajanna Sircilla', 'Ranga Reddy', 'Sangareddy', 'Siddipet',
                'Suryapet', 'Vikarabad', 'Wanaparthy', 'Warangal', 'Yadadri Bhuvanagiri',
            ],
            'TR' => [
                'Dhalai', 'Gomati', 'Khowai', 'North Tripura', 'Sepahijala', 'South Tripura',
                'Unakoti', 'West Tripura',
            ],
            'UP' => [
                'Agra', 'Aligarh', 'Ambedkar Nagar', 'Amethi', 'Amroha', 'Auraiya', 'Ayodhya',
                'Azamgarh', 'Baghpat', 'Bahraich', 'Ballia', 'Balrampur', 'Banda', 'Barabanki',
                'Bareilly', 'Basti', 'Bhadohi', 'Bijnor', 'Budaun', 'Bulandshahr', 'Chandauli',
                'Chitrakoot', 'Deoria', 'Etah', 'Etawah', 'Farrukhabad', 'Fatehpur', 'Firozabad',
                'Gautam Buddha Nagar', 'Ghaziabad', 'Ghazipur', 'Gonda', 'Gorakhpur', 'Hamirpur',
                'Hapur', 'Hardoi', 'Hathras', 'Jalaun', 'Jaunpur', 'Jhansi', 'Kannauj', 'Kanpur Dehat',
                'Kanpur Nagar', 'Kasganj', 'Kaushambi', 'Kushinagar', 'Lakhimpur Kheri', 'Lalitpur',
                'Lucknow', 'Maharajganj', 'Mahoba', 'Mainpuri', 'Mathura', 'Mau', 'Meerut', 'Mirzapur',
                'Moradabad', 'Muzaffarnagar', 'Pilibhit', 'Pratapgarh', 'Prayagraj', 'Rae Bareli',
                'Rampur', 'Saharanpur', 'Sambhal', 'Sant Kabir Nagar', 'Shahjahanpur', 'Shamli',
                'Shravasti', 'Siddharthnagar', 'Sitapur', 'Sonbhadra', 'Sultanpur', 'Unnao',
                'Varanasi',
            ],
            'UK' => [
                'Almora', 'Bageshwar', 'Chamoli', 'Champawat', 'Dehradun', 'Haridwar', 'Nainital',
                'Pauri Garhwal', 'Pithoragarh', 'Rudraprayag', 'Tehri Garhwal', 'Udham Singh Nagar',
                'Uttarkashi',
            ],
            'WB' => [
                'Alipurduar', 'Arambagh', 'Bankura', 'Basirhat', 'Birbhum', 'Cooch Behar', 'Dakshin Dinajpur',
                'Darjeeling', 'Hooghly', 'Howrah', 'Jalpaiguri', 'Jangipur', 'Jhargram', 'Kalimpong',
                'Kolkata', 'Malda', 'Murshidabad', 'Nadia', 'North 24 Parganas', 'Paschim Bardhaman',
                'Paschim Medinipur', 'Purba Bardhaman', 'Purba Medinipur', 'Purulia', 'South 24 Parganas',
                'Sundarbans', 'Uttar Dinajpur',
            ],
            // Union territories
            'AN' => ['Nicobar', 'North and Middle Andaman', 'South Andaman'],
            'CH' => ['Chandigarh'],
            'DH' => ['Dadra and Nagar Haveli', 'Daman', 'Diu'],
            'DL' => [
                'Central Delhi', 'Central North Delhi', 'East Delhi', 'New Delhi', 'North Delhi',
                'North East Delhi', 'North West Delhi', 'Old Delhi', 'Outer North Delhi',
                'South Delhi', 'South East Delhi', 'South West Delhi', 'West Delhi',
            ],
            'JK' => [
                'Anantnag', 'Bandipora', 'Baramulla', 'Budgam', 'Doda', 'Ganderbal', 'Jammu',
                'Kathua', 'Kishtwar', 'Kulgam', 'Kupwara', 'Poonch', 'Pulwama', 'Rajouri', 'Ramban',
                'Reasi', 'Samba', 'Shopian', 'Srinagar', 'Udhampur',
            ],
            'LA' => ['Changthang', 'Drass', 'Kargil', 'Leh', 'Nubra', 'Sham', 'Zanskar'],
            'LD' => ['Lakshadweep'],
            'PY' => ['Karaikal', 'Mahe', 'Puducherry', 'Yanam'],
        ];
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $stateIds = DB::table('states')->pluck('id', 'state_code');
        $now = now();
        $rows = [];

        foreach ($this->districtsByStateCode() as $stateCode => $districts) {
            if (! $stateIds->has($stateCode)) {
                continue;
            }

            $stateId = $stateIds[$stateCode];

            foreach ($districts as $districtName) {
                $rows[] = [
                    'state_id' => $stateId,
                    'district_name' => $districtName,
                    'status' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('districts')->upsert(
                $chunk,
                ['state_id', 'district_name'],
                ['status', 'updated_at']
            );
        }
    }
}
