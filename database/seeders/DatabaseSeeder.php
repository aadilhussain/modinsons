<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Enquiry;
use App\Models\PageView;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /* ---------------- admin ---------------- */
        User::updateOrCreate(
            ['email' => 'admin@modiandsons.com'],
            ['name' => 'K. Kabra', 'password' => 'FTuTzyEEKz*16', 'is_admin' => true]
        );

        /* ---------------- catalogue ---------------- */
        foreach ($this->catalogue() as $i => $c) {
            $cat = Category::updateOrCreate(
                ['slug' => Str::slug($c['name'])],
                [
                    'name' => $c['name'],
                    'tagline' => $c['tagline'],
                    'description' => $c['description'],
                    'icon' => $c['icon'],
                    'sort_order' => $i,
                    'is_active' => true,
                ]
            );

            foreach ($c['products'] as $j => $p) {
                Product::updateOrCreate(
                    ['slug' => Str::slug($p[0])],
                    [
                        'category_id' => $cat->id,
                        'name' => $p[0],
                        'brand' => $p[1],
                        'sku' => 'MS-'.Str::upper(Str::substr($cat->slug, 0, 3)).'-'.str_pad((string) ($j + 1), 3, '0', STR_PAD_LEFT),
                        'short_description' => $p[2],
                        'description' => $p[3],
                        'specs' => $p[4],
                        'unit' => $p[5],
                        'min_order_qty' => $p[6],
                        'badge' => $p[7] ?? null,
                        'is_active' => true,
                        'is_featured' => (bool) ($p[8] ?? false),
                        'sort_order' => $j,
                        'views' => random_int(4, 180),
                    ]
                );
            }
        }

        /* ---------------- demo analytics so the dashboard is not empty ---------------- */
        if (PageView::count() === 0) {
            $paths = [
                ['/', 'Home'], ['/products', 'All Products'],
                ['/category/pvc-pipes-fittings', 'Category'], ['/category/electrical-wires-cables', 'Category'],
                ['/category/ceiling-fans', 'Category'], ['/category/led-lights-panels', 'Category'],
                ['/enquiry', 'Bulk Enquiry'], ['/contact', 'Contact'], ['/about', 'About Us'],
            ];
            $devices = ['mobile', 'mobile', 'mobile', 'desktop', 'tablet'];
            $refs = [null, 'https://www.google.com/', 'https://www.indiamart.com/', 'https://www.justdial.com/', null];

            $rows = [];
            for ($d = 29; $d >= 0; $d--) {
                $date = now()->subDays($d)->toDateString();
                foreach (range(1, random_int(6, 26)) as $n) {
                    [$path, $title] = $paths[array_rand($paths)];
                    $rows[] = [
                        'path' => $path,
                        'title' => $title,
                        'visitor_hash' => hash('sha256', 'demo'.$d.'-'.random_int(1, 14)),
                        'referrer' => $refs[array_rand($refs)],
                        'device' => $devices[array_rand($devices)],
                        'viewed_on' => $date,
                        'created_at' => $date.' 10:00:00',
                        'updated_at' => $date.' 10:00:00',
                    ];
                }
            }
            foreach (array_chunk($rows, 400) as $chunk) {
                PageView::insert($chunk);
            }
        }

        if (Enquiry::count() === 0) {
            $samples = [
                ['Ramesh Sharma', 'Sharma Hardware', '9829012345', 'Udaipur', 'Retailer', '500', 'Metre', 'new'],
                ['Vinod Agarwal', 'Agarwal Electricals', '9414023456', 'Rajsamand', 'Wholesale', '200', 'Piece', 'contacted'],
                ['Mahesh Jain', 'Jain Traders', '9887034567', 'Nathdwara', 'Distributor', '1000', 'Metre', 'quoted'],
                ['Sunil Vyas', 'Vyas Constructions', '9928045678', 'Chittorgarh', 'Contractor', '75', 'Piece', 'won'],
                ['Prakash Soni', null, '9660056789', 'Bhilwara', 'Retailer', '150', 'Piece', 'new'],
                ['Dinesh Paliwal', 'Paliwal Enterprises', '9351067890', 'Udaipur', 'Wholesale', '2000', 'Metre', 'contacted'],
            ];

            $ids = Product::pluck('id')->all();

            foreach ($samples as $k => $s) {
                Enquiry::create([
                    'product_id' => $ids ? $ids[array_rand($ids)] : null,
                    'reference' => 'MS-'.now()->subDays($k * 2)->format('ymd').'-'.Str::upper(Str::random(4)),
                    'name' => $s[0], 'company' => $s[1], 'phone' => $s[2], 'city' => $s[3],
                    'buyer_type' => $s[4], 'quantity' => $s[5], 'unit' => $s[6], 'status' => $s[7],
                    'message' => 'Please share your best wholesale rate and current availability.',
                    'created_at' => now()->subDays($k * 2),
                    'updated_at' => now()->subDays($k * 2),
                ]);
            }
        }
    }

    /**
     * Real product range for Modi And Sons, Nathdwara.
     * [name, brand, short, long, specs[], unit, MOQ, badge, featured]
     */
    protected function catalogue(): array
    {
        return [
            [
                'name' => 'PVC Pipes & Fittings',
                'icon' => 'pipe',
                'tagline' => 'Agricultural, plumbing and column pipes',
                'description' => 'ISI-marked PVC and UPVC pipes for water supply, agriculture, borewell column and electrical conduit applications, plus the full range of matching fittings.',
                'products' => [
                    ['UPVC Water Supply Pipe', 'Supreme', 'Rigid UPVC pipe for pressurised cold water supply lines.', 'ISI-marked rigid UPVC pipe engineered for potable cold water supply. Leak-proof solvent-weld joints, corrosion free, and unaffected by hard water. Supplied in standard 3-metre and 6-metre lengths across all common pressure classes.', ['Material' => 'UPVC', 'Sizes' => '20 mm to 160 mm', 'Pressure Class' => '2.5 / 4 / 6 / 10 kg/cm²', 'Length' => '3 m / 6 m', 'Standard' => 'IS 4985', 'Colour' => 'Grey'], 'Metre', '100 Metre', 'Bestseller', true],
                    ['PVC Agricultural Water Pipe', 'Kisan', 'Heavy-duty irrigation pipe for farm water distribution.', 'Built for agricultural irrigation duty — high impact strength, UV stabilised for open-field exposure, and sized for borewell and canal distribution networks.', ['Material' => 'PVC', 'Sizes' => '63 mm to 200 mm', 'Pressure Class' => '4 / 6 kg/cm²', 'Length' => '6 m', 'Application' => 'Irrigation, farm supply'], 'Metre', '200 Metre', null, true],
                    ['SWR Drainage Pipe', 'Prince', 'Soil, waste and rainwater drainage pipe system.', 'SWR pipes for building drainage — soil, waste and rainwater lines. Ring-fit and solvent-weld options available with the complete fittings range.', ['Material' => 'PVC', 'Sizes' => '75 mm / 110 mm / 160 mm', 'Type' => 'Type A & Type B', 'Standard' => 'IS 13592', 'Length' => '3 m / 6 m'], 'Metre', '100 Metre', null, false],
                    ['Borewell Column Pipe', 'Supreme', 'Threaded column pipe for submersible pump installation.', 'High tensile UPVC column pipe with square threading for submersible pump columns. Rustproof, light to handle and reusable across installations.', ['Material' => 'UPVC', 'Sizes' => '1 inch to 3 inch', 'Length' => '3 m', 'Thread' => 'Square thread', 'Working Depth' => 'Up to 300 m'], 'Piece', '20 Piece', null, false],
                    ['PVC Electrical Conduit Pipe', 'Polycab', 'ISI conduit for concealed electrical wiring.', 'Fire-retardant PVC conduit for concealed and surface electrical wiring. Self-extinguishing grade, high crush resistance, supplied with bends and accessories.', ['Material' => 'PVC (FR grade)', 'Sizes' => '20 mm / 25 mm / 32 mm', 'Type' => 'Medium & Heavy duty', 'Length' => '3 m', 'Standard' => 'IS 9537'], 'Piece', '50 Piece', null, false],
                    ['UPVC Pipe Fittings Set', 'Astral', 'Elbows, tees, couplers, unions and reducers.', 'Complete matching fittings range for UPVC plumbing lines — elbows, tees, couplers, unions, reducers, end caps and threaded adaptors in all standard sizes.', ['Material' => 'UPVC', 'Types' => 'Elbow, Tee, Coupler, Union, Reducer', 'Sizes' => '20 mm to 110 mm', 'Joint' => 'Solvent weld / Threaded'], 'Piece', '100 Piece', null, false],
                ],
            ],
            [
                'name' => 'Electrical Wires & Cables',
                'icon' => 'wire',
                'tagline' => 'House wiring, flexible and submersible cable',
                'description' => 'FR and FRLS grade copper house wiring cable, multicore flexible cable and flat submersible pump cable from ISI-marked brands.',
                'products' => [
                    ['FR House Wiring Cable', 'Polycab', 'Flame-retardant single core copper wire for domestic wiring.', 'ISI-marked flame-retardant PVC insulated single core copper wire for domestic and commercial concealed wiring. 99.97% electrolytic grade bright annealed copper for low resistance and cool running.', ['Conductor' => '99.97% bare copper', 'Sizes' => '0.75 / 1.0 / 1.5 / 2.5 / 4.0 / 6.0 sq mm', 'Insulation' => 'FR PVC', 'Voltage' => '1100 V', 'Length' => '90 m coil', 'Standard' => 'IS 694'], 'Coil', '10 Coil', 'Bestseller', true],
                    ['FRLS Low Smoke Wire', 'Havells', 'Low smoke, halogen reduced cable for safer installations.', 'FRLS grade wire that emits low smoke and reduced halogen when exposed to fire — specified for hospitals, schools, hotels and public buildings.', ['Conductor' => 'Electrolytic copper', 'Sizes' => '1.0 to 6.0 sq mm', 'Insulation' => 'FRLS PVC', 'Voltage' => '1100 V', 'Length' => '90 m coil'], 'Coil', '10 Coil', null, false],
                    ['Multicore Flexible Cable', 'Finolex', 'Two, three and four core flexible copper cable.', 'Multistrand flexible copper cable for appliances, panels and portable equipment. Tough PVC sheath over colour-coded cores.', ['Cores' => '2 / 3 / 4 core', 'Sizes' => '0.75 to 6.0 sq mm', 'Conductor' => 'Multistrand copper', 'Sheath' => 'PVC', 'Length' => '100 m'], 'Coil', '5 Coil', null, false],
                    ['Flat Submersible Pump Cable', 'Polycab', 'Water-resistant flat cable for submersible pump sets.', 'Three core flat submersible cable designed for continuous immersion. Heavy duty water-resistant PVC sheath with high tensile copper conductors.', ['Cores' => '3 core flat', 'Sizes' => '1.5 to 10 sq mm', 'Application' => 'Submersible pumps', 'Insulation' => 'Water resistant PVC'], 'Metre', '100 Metre', null, false],
                    ['Copper Winding Wire', 'Rajratan', 'Enamelled copper wire for motor and transformer winding.', 'Super enamelled copper winding wire for motor rewinding, transformers and coils. Available in a full range of SWG sizes.', ['Type' => 'Super enamelled', 'Sizes' => '18 SWG to 40 SWG', 'Conductor' => 'Electrolytic copper', 'Class' => 'Class F / H'], 'Kilogram', '5 Kilogram', null, false],
                ],
            ],
            [
                'name' => 'Ceiling Fans',
                'icon' => 'fan',
                'tagline' => 'Standard, high-speed and decorative models',
                'description' => 'Energy efficient ceiling fans in standard, high-speed, BLDC and decorative models from leading Indian brands.',
                'products' => [
                    ['High Speed Ceiling Fan 1200mm', 'Usha', 'High air delivery 1200 mm sweep ceiling fan.', 'High speed ceiling fan with 1200 mm sweep and aerodynamically profiled aluminium blades for strong air delivery in living rooms and shops. Double ball bearing motor for quiet, long running.', ['Sweep' => '1200 mm', 'Speed' => '380 RPM', 'Air Delivery' => '230 CMM', 'Power' => '75 W', 'Voltage' => '230 V AC', 'Warranty' => '2 years'], 'Piece', '10 Piece', 'Bestseller', true],
                    ['BLDC Energy Saving Fan', 'Atomberg', '5-star BLDC fan with remote, 28 W consumption.', 'BLDC motor ceiling fan consuming roughly one third the power of a conventional fan. Supplied with remote control, timer and speed memory.', ['Sweep' => '1200 mm', 'Power' => '28 W', 'Air Delivery' => '235 CMM', 'Rating' => '5 Star BEE', 'Features' => 'Remote, timer, LED indicator', 'Warranty' => '2 years + 1 year motor'], 'Piece', '5 Piece', 'Energy Saver', true],
                    ['Decorative Ceiling Fan', 'Crompton', 'Designer fan with premium finish for living areas.', 'Decorative ceiling fan with a premium powder-coated finish and ornamental trims, designed for living and dining rooms.', ['Sweep' => '1200 mm', 'Speed' => '350 RPM', 'Power' => '74 W', 'Finish' => 'Powder coated, multiple shades'], 'Piece', '5 Piece', null, false],
                    ['Economy Ceiling Fan 900mm', 'Bajaj', 'Compact 900 mm fan for small rooms and shops.', 'Compact 900 mm sweep fan suited to small rooms, kitchens, shops and cabins where a full-size fan is oversized.', ['Sweep' => '900 mm', 'Speed' => '400 RPM', 'Power' => '50 W', 'Warranty' => '2 years'], 'Piece', '10 Piece', null, false],
                ],
            ],
            [
                'name' => 'Table & Wall Fans',
                'icon' => 'tablefan',
                'tagline' => 'Table, wall mount, pedestal and exhaust',
                'description' => 'Table fans, wall-mounted fans, pedestal fans and exhaust fans for homes, shops, workshops and offices.',
                'products' => [
                    ['Table Fan 400mm', 'Usha', 'Portable 400 mm table fan with oscillation.', 'Portable table fan with 400 mm sweep, wide-angle oscillation and three-speed control. Stable weighted base and thermal overload protection.', ['Sweep' => '400 mm', 'Speed' => '1350 RPM', 'Power' => '55 W', 'Oscillation' => 'Yes', 'Speeds' => '3'], 'Piece', '10 Piece', 'Bestseller', true],
                    ['Wall Mounting Fan 450mm', 'Bajaj', 'Space-saving wall fan for shops and workshops.', 'Wall-mounted fan that frees up floor space — ideal for shops, workshops, canteens and small factories. Pull-cord speed control with wide oscillation.', ['Sweep' => '450 mm', 'Speed' => '1350 RPM', 'Power' => '70 W', 'Mounting' => 'Wall bracket', 'Control' => 'Pull cord'], 'Piece', '5 Piece', null, false],
                    ['Pedestal Stand Fan 450mm', 'Crompton', 'Height-adjustable pedestal fan.', 'Height-adjustable pedestal fan with a stable cross base, wide oscillation and three-speed control for halls, functions and workshops.', ['Sweep' => '450 mm', 'Power' => '75 W', 'Height' => 'Adjustable', 'Speeds' => '3'], 'Piece', '5 Piece', null, false],
                    ['Exhaust Fan 250mm', 'Havells', 'Kitchen and bathroom exhaust fan.', 'Exhaust fan for kitchens, bathrooms and small commercial spaces. Efficient extraction with a rust-resistant body and easy-clean grille.', ['Sweep' => '250 mm', 'Power' => '40 W', 'Application' => 'Kitchen, bathroom', 'Body' => 'Rust resistant'], 'Piece', '10 Piece', null, false],
                ],
            ],
            [
                'name' => 'LED Lights & Panels',
                'icon' => 'led',
                'tagline' => 'Panel lights, battens, bulbs and floodlights',
                'description' => 'Complete LED lighting range — panel lights, batten tubes, bulbs, downlighters, streetlights and floodlights, including the Syska range.',
                'products' => [
                    ['LED Panel Light Round', 'Syska', 'Slim recessed round panel light, 3W to 22W.', 'Slim recessed round LED panel with uniform diffused output and no visible glare. Long rated life with a driver supplied in the box.', ['Wattage' => '3 / 6 / 12 / 15 / 18 / 22 W', 'Shape' => 'Round', 'Colour' => 'Cool White 6500K / Warm 3000K', 'Life' => '25,000 hours', 'Warranty' => '2 years'], 'Piece', '25 Piece', 'Bestseller', true],
                    ['LED Panel Light Square', 'Syska', 'Square recessed panel for false ceilings.', 'Square recessed LED panel designed for false ceiling grids in offices, showrooms and homes. Even illumination with a slim profile.', ['Wattage' => '6 / 12 / 18 / 22 W', 'Shape' => 'Square', 'Colour' => '6500K / 3000K', 'Life' => '25,000 hours'], 'Piece', '25 Piece', null, false],
                    ['LED Batten Tube Light', 'Philips', 'Linear batten replacement for tube lights.', 'Linear LED batten that replaces conventional fluorescent tubes — instant start, no flicker, no starter or choke required.', ['Wattage' => '10 / 18 / 20 / 22 W', 'Length' => '2 ft / 4 ft', 'Colour' => '6500K Cool Daylight', 'Life' => '20,000 hours'], 'Piece', '25 Piece', null, true],
                    ['LED Bulb B22', 'Syska', 'Standard pin-type LED bulb, 5W to 18W.', 'Standard B22 pin-type LED bulb with wide voltage tolerance suited to Indian supply conditions. Available across the full wattage range.', ['Wattage' => '5 / 7 / 9 / 12 / 15 / 18 W', 'Base' => 'B22 pin type', 'Colour' => '6500K / 3000K', 'Voltage' => '140-300 V', 'Warranty' => '1 year'], 'Piece', '50 Piece', null, false],
                    ['LED Flood Light', 'Havells', 'Outdoor floodlight, 10W to 200W.', 'Weatherproof outdoor LED floodlight for compounds, godowns, signage and construction sites. Die-cast aluminium body with toughened glass.', ['Wattage' => '10 / 20 / 50 / 100 / 150 / 200 W', 'IP Rating' => 'IP65', 'Body' => 'Die-cast aluminium', 'Colour' => '6500K'], 'Piece', '10 Piece', null, false],
                    ['LED Street Light', 'Syska', 'IP66 street light for roads and compounds.', 'IP66 rated LED street light for roads, colonies and industrial compounds. High lumen output with surge protection.', ['Wattage' => '24 / 36 / 50 / 72 / 100 W', 'IP Rating' => 'IP66', 'Mounting' => 'Pole bracket', 'Surge' => '4 kV'], 'Piece', '10 Piece', null, false],
                ],
            ],
            [
                'name' => 'Water Pumps & Motors',
                'icon' => 'motor',
                'tagline' => 'Monoblock, submersible and openwell pumps',
                'description' => 'Domestic and agricultural water pumps — monoblock, self-priming, openwell and borewell submersible sets with matching control panels.',
                'products' => [
                    ['Monoblock Water Pump 1HP', 'Crompton', 'Single phase 1 HP monoblock for domestic supply.', 'Single phase 1 HP monoblock pump for domestic overhead tank filling and small-scale irrigation. Dynamically balanced impeller and cast iron body for long duty life.', ['Power' => '1 HP', 'Phase' => 'Single phase', 'Head' => 'Up to 30 m', 'Discharge' => 'Up to 100 LPM', 'Outlet' => '25 mm / 32 mm', 'Body' => 'Cast iron'], 'Piece', '2 Piece', 'Bestseller', true],
                    ['Borewell Submersible Pump', 'Kirloskar', 'V4 submersible pump set for borewells.', 'V4 borewell submersible pump set with water-filled motor, supplied with control panel. Built for continuous agricultural and domestic borewell duty.', ['Power' => '1 / 1.5 / 2 / 3 / 5 HP', 'Type' => 'V4 borewell', 'Bore Size' => '4 inch', 'Phase' => 'Single / Three phase', 'Includes' => 'Control panel'], 'Set', '1 Set', null, true],
                    ['Openwell Submersible Pump', 'Texmo', 'Openwell pump for wells and sumps.', 'Openwell submersible pump for open wells, sumps and tanks. Corrosion resistant with high efficiency across a wide head range.', ['Power' => '1 / 2 / 3 / 5 HP', 'Type' => 'Openwell submersible', 'Phase' => 'Single / Three phase'], 'Piece', '1 Piece', null, false],
                    ['Self Priming Pump 0.5HP', 'Havells', 'Compact self-priming pump for household use.', 'Compact self-priming monoblock pump for household water supply and pressure boosting. Quiet running with thermal overload protection.', ['Power' => '0.5 HP', 'Head' => 'Up to 25 m', 'Type' => 'Self priming', 'Phase' => 'Single phase'], 'Piece', '5 Piece', null, false],
                    ['Single Phase Motor Starter', 'L&T', 'Control panel and starter for pump sets.', 'Single phase motor starter with overload and no-volt protection for pump sets. Protects the motor against dry run and voltage fluctuation.', ['Type' => 'Single phase starter', 'Rating' => 'Up to 3 HP', 'Protection' => 'Overload, no-volt'], 'Piece', '5 Piece', null, false],
                ],
            ],
            [
                'name' => 'Tarpaulin & Tirpal',
                'icon' => 'tarp',
                'tagline' => 'HDPE and PVC waterproof covers',
                'description' => 'Waterproof HDPE and PVC laminated tarpaulin (tirpal) for agriculture, transport, construction and godown covering in all standard sizes and GSM.',
                'products' => [
                    ['HDPE Waterproof Tarpaulin', 'Tarpolin', 'UV stabilised HDPE tirpal for general covering.', 'UV stabilised HDPE laminated tarpaulin for covering crops, stock, vehicles and construction material. Fully waterproof with reinforced hemmed edges and rustproof eyelets.', ['Material' => 'HDPE laminated', 'GSM' => '90 / 120 / 150 / 200 GSM', 'Sizes' => '6x9, 9x12, 12x15, 15x18 ft & custom', 'Eyelets' => 'Rustproof metal', 'UV Treated' => 'Yes'], 'Piece', '10 Piece', 'Bestseller', true],
                    ['Heavy Duty PVC Tarpaulin', 'Supreme', 'Reinforced PVC tarpaulin for transport and industry.', 'Heavy duty PVC coated tarpaulin for truck bodies, industrial covering and long outdoor exposure. High tear strength and fully waterproof.', ['Material' => 'PVC coated fabric', 'GSM' => '250 / 350 / 450 GSM', 'Application' => 'Truck body, industrial', 'Waterproof' => '100%'], 'Piece', '5 Piece', null, false],
                    ['Agricultural Crop Cover Tirpal', 'Kisan', 'Farm-grade tirpal for grain and produce.', 'Farm-grade tarpaulin for drying, threshing and covering harvested grain and produce. Light to handle and easy to fold.', ['Material' => 'HDPE', 'GSM' => '90 / 120 GSM', 'Sizes' => 'Standard & custom', 'Application' => 'Grain drying, crop cover'], 'Piece', '10 Piece', null, false],
                ],
            ],
            [
                'name' => 'Fencing & Barbed Wire',
                'icon' => 'fence',
                'tagline' => 'Barbed, GI, binding and chain link',
                'description' => 'Galvanised barbed wire, GI wire, binding wire and chain link fencing mesh in all standard gauges for boundary, agricultural and construction use.',
                'products' => [
                    ['Galvanised Barbed Wire', 'Tata Wiron', 'GI barbed wire for boundary and farm fencing.', 'Hot-dip galvanised barbed wire for boundary walls, farm perimeters and industrial compounds. Consistent barb spacing with high tensile strength and long rust-free life.', ['Material' => 'Galvanised iron', 'Gauge' => '12 / 14 SWG', 'Barb Spacing' => '3 to 6 inch', 'Coating' => 'Hot dip galvanised', 'Packing' => '25 / 50 kg coil'], 'Kilogram', '50 Kilogram', 'Bestseller', true],
                    ['GI Wire All Gauge', 'Tata Wiron', 'Galvanised iron wire in all standard gauges.', 'Galvanised iron wire supplied across the full gauge range for fencing, binding, tying and general fabrication work.', ['Material' => 'Galvanised iron', 'Gauge' => '8 SWG to 22 SWG', 'Coating' => 'Galvanised', 'Packing' => 'Coil'], 'Kilogram', '50 Kilogram', null, false],
                    ['Chain Link Fencing Mesh', 'Local', 'GI chain link mesh for boundary fencing.', 'Galvanised chain link mesh for boundary fencing, playgrounds, plantations and industrial compounds. Supplied in rolls of standard height and mesh size.', ['Material' => 'GI', 'Mesh Size' => '25 / 50 / 75 mm', 'Height' => '3 ft to 8 ft', 'Gauge' => '10 / 12 SWG'], 'Square Feet', '500 Square Feet', null, false],
                    ['MS Binding Wire', 'Local', 'Annealed binding wire for construction.', 'Soft annealed mild steel binding wire used for tying reinforcement bars at construction sites. Supplied in standard coils.', ['Material' => 'Mild steel, annealed', 'Gauge' => '18 / 20 SWG', 'Packing' => '25 kg coil'], 'Kilogram', '25 Kilogram', null, false],
                ],
            ],
            [
                'name' => 'Electrical Accessories',
                'icon' => 'switch',
                'tagline' => 'Switches, MCBs, boxes and fittings',
                'description' => 'Modular switches and sockets, MCBs and distribution boards, junction boxes, holders and the full range of everyday electrical fittings.',
                'products' => [
                    ['Modular Switch & Socket', 'Anchor', 'Modular switches, sockets and plates.', 'Complete modular wiring devices range — switches, sockets, regulators, indicators and matching cover plates in multiple finishes.', ['Type' => 'Modular', 'Rating' => '6 A / 16 A', 'Finish' => 'White, Ivory, Silver', 'Range' => 'Switches, sockets, regulators'], 'Piece', '100 Piece', null, false],
                    ['MCB Circuit Breaker', 'Havells', 'Single and triple pole MCBs.', 'Miniature circuit breakers offering reliable short-circuit and overload protection for domestic and commercial distribution boards.', ['Poles' => 'SP / DP / TP', 'Rating' => '6 A to 63 A', 'Breaking Capacity' => '10 kA', 'Curve' => 'B / C'], 'Piece', '25 Piece', null, false],
                    ['Distribution Board', 'Legrand', 'Metal DB enclosures, 4 to 16 way.', 'Powder-coated metal distribution boards with a hinged door and DIN rail, available from 4 way to 16 way.', ['Ways' => '4 / 6 / 8 / 12 / 16 way', 'Type' => 'SPN / TPN', 'Body' => 'Powder coated metal'], 'Piece', '10 Piece', null, false],
                    ['PVC Junction Box', 'Precision', 'Concealed and surface junction boxes.', 'PVC junction and modular boxes for concealed and surface electrical installations, in all standard module sizes.', ['Material' => 'PVC', 'Sizes' => '1M to 12M', 'Type' => 'Concealed / Surface'], 'Piece', '100 Piece', null, false],
                ],
            ],
        ];
    }
}
