<?php
/**
 * Starter copy for the home page, in Vietnamese and English (ported from the Next.js site's
 * src/lib/content.ts). Only used to build the block patterns and the demo pages; once a page
 * exists, its text lives in the page and is edited in the block editor.
 *
 * @package elio
 */

defined( 'ABSPATH' ) || exit;

/** Image URL inside the theme's assets/img folder. */
function elio_img( $path ) {
	return get_theme_file_uri( 'assets/img/' . $path );
}

/** "_" marks a non-breaking space so Vietnamese compound words never split across lines. */
function elio_nb( $s ) {
	return str_replace( '_', "\u{00A0}", $s );
}

/**
 * @param string $lang 'vi' or 'en'.
 * @return array
 */
function elio_content( $lang ) {
	$en = 'en' === $lang;
	$t  = $en ? elio_copy_en() : elio_copy_vi();

	$icon = fn( $n ) => elio_img( 'icon-' . $n . '.png' );

	$quick = [
		[ 'sun', '18 tháng – 6 tuổi', 'ĐỘ TUỔI TUYỂN SINH', '18 months – 6 years', 'AGES' ],
		[ 'sprout', 'Môi trường quốc tế', 'SONG NGỮ', 'International setting', 'BILINGUAL' ],
		[ 'leaf', 'Thảo Điền, TP. HCM', 'VỊ TRÍ', 'Thao Dien, HCMC', 'LOCATION' ],
		[ 'circle', 'Lớp học quy mô nhỏ', 'CÁ NHÂN HÓA', 'Small class sizes', 'PERSONALISED' ],
		[ 'sprout', 'Không gian tự nhiên', 'NUÔI DƯỠNG', 'Natural spaces', 'NURTURING' ],
		[ 'circle', 'Cộng đồng gắn kết', 'ĐỒNG HÀNH', 'A close community', 'PARTNERSHIP' ],
	];

	$pillars = [
		[ '01', 'LET CURIOSITY LEAD', 'sun', 'Để sự tò mò dẫn đường.', 'Tại ELIO, trẻ không chỉ được nghe câu trả lời. Con được chạm, quan sát, thử nghiệm, đặt câu hỏi và tự tìm ra điều mình muốn biết.', 'Từ “Con đang làm gì?” → “Con nghĩ điều gì sẽ xảy ra?”', 'Let curiosity lead the way.', 'At ELIO, children don’t just hear the answers. They touch, observe, experiment, ask questions and find out for themselves what they want to know.', 'From “What are you doing?” → “What do you think will happen?”' ],
		[ '02', 'SPACE TO BECOME', 'sprout', 'Cho con không gian để trở thành chính mình.', 'Thay vì yêu cầu mọi trẻ cùng đạt một “chuẩn” giống nhau, ELIO quan sát con thích gì, con cần thêm thời gian ở đâu, con đang sẵn sàng cho điều gì, rồi tạo ra cơ hội học tập phù hợp.', 'We don’t rush childhood. Chúng tôi không vội vàng với tuổi thơ.', 'Give children room to become themselves.', 'Rather than asking every child to meet the same “standard”, ELIO notices what each child loves, where they need more time and what they are ready for — then creates the right learning opportunities.', 'We don’t rush childhood.' ],
		[ '03', 'CONFIDENCE BEFORE PERFORMANCE', 'leaf', 'Tự tin trước khi thành tích.', 'Con được khuyến khích tự lựa chọn, tự diễn đạt, thử điều mới và giải quyết vấn đề. Một đứa trẻ tự tin học cách đứng lên sau thất bại có nền tảng vững hơn một đứa trẻ chỉ quen làm đúng.', 'Try → Make mistakes → Try again → Discover', 'Confidence before performance.', 'Children are encouraged to choose, express themselves, try new things and solve problems. A confident child who learns to get back up after a setback has a stronger foundation than one who only knows how to get it right.', 'Try → Make mistakes → Try again → Discover' ],
		[ '04', 'THE WORLD, AT CHILD’S HEIGHT', 'circle', 'Mở rộng thế giới — theo cách trẻ có thể chạm tới.', 'ELIO đưa trẻ tiếp xúc với ngôn ngữ, văn hóa, con người và những góc nhìn đa dạng ngay từ những năm đầu đời, bắt đầu từ những điều rất gần gũi.', 'Global mindset begins with everyday curiosity.', 'Widening the world — in ways children can reach.', 'ELIO introduces children to languages, cultures, people and different perspectives from their earliest years, starting with what is close and familiar.', 'Global mindset begins with everyday curiosity.' ],
		[ '05', 'GROW TOGETHER', 'sprout', 'Con lớn lên. Gia đình cũng lớn lên cùng con.', 'Nhà trường chia sẻ với ba mẹ con đang học gì, đang thay đổi thế nào, điều gì khiến con hào hứng và ba mẹ có thể tiếp tục đồng hành ra sao tại nhà.', 'Because raising a child is a journey we share.', 'Children grow. Families grow with them.', 'We share with parents what their child is learning, how they are changing, what excites them and how to keep the journey going at home.', 'Because raising a child is a journey we share.' ],
	];

	// [image, object-position class, tint class, time, VI name, VI part, VI body, EN name, EN part, EN body]
	$day = [
		[ 'day/01-welcome.jpg', '[&_img]:object-[60%_50%]', 'bg-butter', '08:00', 'Chào ngày mới', 'BUỔI SÁNG', 'Con được chào đón trong một không gian ấm áp, an toàn. Mỗi ngày bắt đầu bằng những lời chào và sự kết nối thân quen từ cô giáo.', 'Good morning', 'MORNING', 'Children are welcomed into a warm, safe space. Every day begins with greetings and a familiar connection with their teacher.' ],
		[ 'day/02-learn.jpg', '[&_img]:object-[50%_40%]', 'bg-sage-tint', '08:30', 'Khám phá & học tập', 'BUỔI SÁNG', 'Con học qua trải nghiệm, tự do khám phá theo sở thích và nhịp độ của mình. Giáo viên quan sát, đồng hành và khơi mở câu hỏi mới.', 'Explore & learn', 'MORNING', 'Children learn through experience, exploring freely at their own pace and interests. Teachers observe, guide and spark new questions.' ],
		[ 'day/03-outdoor.jpg', '[&_img]:object-[50%_60%]', 'bg-sage-tint', '10:00', 'Vui chơi ngoài trời', 'BUỔI SÁNG', 'Con vận động, quan sát và kết nối với thiên nhiên. Mỗi trải nghiệm ngoài trời là một cơ hội khám phá thế giới xung quanh.', 'Outdoor play', 'MORNING', 'Children move, observe and connect with nature. Every moment outside is a chance to discover the world around them.' ],
		[ 'day/04-lunch.jpg', '[&_img]:object-[50%_45%]', 'bg-butter', '11:30', 'Bữa trưa lành mạnh', 'BUỔI TRƯA', 'Những bữa ăn ngon, cân bằng và phù hợp với nhu cầu phát triển của trẻ, từ nguyên liệu tươi được chọn kỹ mỗi ngày.', 'A healthy lunch', 'MIDDAY', 'Tasty, balanced meals suited to growing children, made from fresh ingredients chosen with care every day.' ],
		[ 'day/05-rest.jpg', '[&_img]:object-[50%_60%]', 'bg-lilac', '13:00', 'Giấc ngủ trưa', 'BUỔI TRƯA', 'Một khoảng lặng cần thiết để con nghỉ ngơi và lắng nghe nhịp sinh học của mình.', 'Nap time', 'MIDDAY', 'A needed pause for children to rest and listen to their own rhythm.' ],
		[ 'day/06-art.jpg', '[&_img]:object-[50%_50%]', 'bg-butter', '14:30', 'Sáng tạo & kỹ năng', 'BUỔI CHIỀU', 'Con khám phá nghệ thuật, ngôn ngữ, vận động và kỹ năng sống thông qua những trải nghiệm thực tế.', 'Creativity & skills', 'AFTERNOON', 'Children explore art, language, movement and life skills through hands-on experiences.' ],
		[ 'day/07-goodbye.jpg', '[&_img]:object-[50%_40%]', 'bg-sage-tint', '16:00', 'Kết nối & tạm biệt', 'BUỔI CHIỀU', 'Con chia sẻ những điều thú vị trong ngày và trở về với thật nhiều niềm vui.', 'Connect & goodbye', 'AFTERNOON', 'Children share the best moments of their day and head home full of joy.' ],
	];

	$nutri = [
		[ 'leaf', 'bg-sage-tint', 'Nguyên liệu tươi & an toàn', 'Lựa chọn kỹ lưỡng mỗi ngày.', 'Fresh & safe ingredients', 'Carefully chosen every day.' ],
		[ 'sun', 'bg-butter', 'Bữa ăn cân bằng', 'Phù hợp với độ tuổi và nhu cầu phát triển.', 'Balanced meals', 'Suited to each age and stage of growth.' ],
		[ 'sprout', 'bg-sage-tint', 'Thói quen ăn uống lành mạnh', 'Giúp con khám phá và yêu thích thực phẩm tự nhiên.', 'Healthy eating habits', 'Helping children discover and love natural foods.' ],
	];
	// [image, desktop placement + tilt classes, VI caption, EN caption]
	$polaroids = [
		[ 'day/lunch.jpg', 'rotate-[3deg] lg:top-0 lg:right-[20px]', 'Bữa trưa đầy đủ dinh dưỡng', 'A wholesome lunch' ],
		[ 'day/fresh-fruit.jpg', '-rotate-[4deg] lg:top-[190px] lg:right-[64px]', 'Món ăn tươi ngon mỗi ngày', 'Fresh dishes every day' ],
		[ 'day/fruit.jpg', 'rotate-[3deg] lg:top-[378px] lg:right-[16px]', 'Trái cây theo mùa', 'Seasonal fruit' ],
	];

	// [n, EN label, icon, tint class, number colour class, VI title, VI body, VI chips, EN title, EN body, EN chips]
	$approach = [
		[ '01', 'CHILD-CENTERED LEARNING', 'sun', 'bg-butter', 'text-[#A8823F]', 'Lấy trẻ làm trung tâm', 'Mỗi đứa trẻ là một cá thể độc đáo. ELIO tôn trọng nhịp độ, sở thích và cách học riêng của từng trẻ.', [ 'Tự tin', 'Tự chủ', 'Chủ động học hỏi' ], 'Child-centred', 'Every child is unique. ELIO respects each child’s pace, interests and way of learning.', [ 'Confidence', 'Independence', 'Initiative' ] ],
		[ '02', 'LEARNING THROUGH PLAY', 'sprout', 'bg-sage-tint', 'text-[#3E6B4F]', 'Học qua vui chơi & khám phá', 'Trẻ học nhiều nhất khi được chạm, thử, quan sát và tự mình khám phá thế giới xung quanh.', [ 'Tò mò', 'Giải quyết vấn đề', 'Yêu học tập' ], 'Learning through play & discovery', 'Children learn best when they can touch, try, observe and explore the world for themselves.', [ 'Curiosity', 'Problem-solving', 'Love of learning' ] ],
		[ '03', 'BILINGUAL ENVIRONMENT', 'circle', 'bg-lilac', 'text-[#6A5A92]', 'Song ngữ, hội nhập quốc tế', 'Ngôn ngữ, văn hóa và góc nhìn đa dạng được đưa vào trải nghiệm hằng ngày một cách tự nhiên.', [ 'Ngôn ngữ', 'Giao tiếp', 'Tư duy toàn cầu' ], 'Bilingual, globally minded', 'Languages, cultures and diverse perspectives are woven naturally into everyday experiences.', [ 'Language', 'Communication', 'Global thinking' ] ],
		[ '04', 'SOCIAL-EMOTIONAL DEVELOPMENT', 'leaf', 'bg-peach', 'text-[#A9603F]', 'Phát triển cảm xúc – xã hội', 'ELIO giúp trẻ hiểu cảm xúc, xây dựng mối quan hệ và học cách kết nối với cộng đồng.', [ 'Tự tin', 'Đồng cảm', 'Hợp tác' ], 'Social-emotional growth', 'ELIO helps children understand feelings, build relationships and connect with their community.', [ 'Confidence', 'Empathy', 'Collaboration' ] ],
	];

	$grow = [
		[ '01', 'A CURIOUS LEARNER', 'Một người học tò mò', 'Con luôn được khuyến khích đặt câu hỏi, quan sát và khám phá thế giới xung quanh.', [ 'Tò mò', 'Tư duy độc lập', 'Yêu thích học tập' ], 'A curious learner', 'Children are always encouraged to ask questions, observe and explore the world around them.', [ 'Curiosity', 'Independent thinking', 'A love of learning' ] ],
		[ '02', 'A CONFIDENT INDIVIDUAL', 'Một cá nhân tự tin', 'Con được trao cơ hội thử, sai, lựa chọn và thể hiện bản thân trong một môi trường an toàn.', [ 'Tự tin thể hiện', 'Tự chủ trong lựa chọn', 'Kiên trì với thử thách' ], 'A confident individual', 'Children get the chance to try, make mistakes, choose and express themselves in a safe environment.', [ 'Confident self-expression', 'Ownership of choices', 'Perseverance' ] ],
		[ '03', 'A KIND & CONNECTED CITIZEN', 'Một công dân tử tế và biết kết nối', 'Con học cách lắng nghe, thấu hiểu và tôn trọng những người xung quanh.', [ 'Đồng cảm', 'Hợp tác và chia sẻ', 'Tôn trọng sự khác biệt' ], 'A kind & connected citizen', 'Children learn to listen to, understand and respect the people around them.', [ 'Empathy', 'Cooperation & sharing', 'Respect for difference' ] ],
		[ '04', 'A GLOBAL MINDSET', 'Tư duy rộng mở', 'Con được tiếp xúc với ngôn ngữ, văn hóa và những góc nhìn đa dạng, từ đó dần hiểu hơn về thế giới.', [ 'Giao tiếp đa ngôn ngữ', 'Tò mò về thế giới', 'Tư duy linh hoạt' ], 'A global mindset', 'Children meet languages, cultures and diverse perspectives, and slowly come to understand the world.', [ 'Multilingual communication', 'Curiosity about the world', 'Flexible thinking' ] ],
		[ '05', 'A HAPPY & RESILIENT CHILD', 'Một đứa trẻ hạnh phúc và vững vàng', 'Con học cách nhận biết cảm xúc, vượt qua những thử thách nhỏ và tìm thấy niềm vui trong hành trình lớn lên.', [ 'Nhận thức cảm xúc', 'Khả năng thích nghi', 'Tinh thần tích cực' ], 'A happy & resilient child', 'Children learn to recognise feelings, overcome small challenges and find joy in growing up.', [ 'Emotional awareness', 'Adaptability', 'A positive spirit' ] ],
	];

	$campus = [
		[ 'campus/01-exterior.jpg', '01', 'EXTERIOR', 'Không gian bên ngoài', 'Một không gian xanh, thân thiện và an toàn ngay từ khoảnh khắc con bước qua cánh cổng ELIO.', 'Outdoor grounds', 'A green, friendly and safe space from the moment your child steps through the ELIO gate.' ],
		[ 'campus/02-reception.jpg', '02', 'RECEPTION', 'Khu vực đón tiếp', 'Nơi mỗi ngày bắt đầu bằng một lời chào thân thiện và sự kết nối quen thuộc.', 'Reception', 'Where every day begins with a warm hello and a familiar connection.' ],
		[ 'campus/03-classroom.jpg', '03', 'CLASSROOM', 'Phòng học', 'Không gian mở, nhiều ánh sáng, bố trí để trẻ chủ động khám phá, học tập và tương tác.', 'Classrooms', 'Open, light-filled rooms arranged so children can explore, learn and interact on their own.' ],
		[ 'campus/04-outdoor.jpg', '04', 'OUTDOOR AREA', 'Khu vui chơi ngoài trời', 'Để trẻ vận động, tiếp xúc với thiên nhiên và khám phá thế giới bằng chính đôi tay của mình.', 'Outdoor play area', 'Room to move, touch nature and discover the world with their own hands.' ],
		[ 'campus/05-library.jpg', '05', 'LIBRARY', 'Góc đọc sách', 'Một khoảng không yên tĩnh để con làm quen với sách, câu chuyện và nuôi dưỡng trí tưởng tượng.', 'Reading corner', 'A quiet nook to meet books and stories and grow their imagination.' ],
		[ 'campus/06-art.jpg', '06', 'ART & CREATIVE', 'Góc nghệ thuật & sáng tạo', 'Nơi con tự do tạo ra, thử nghiệm và thể hiện những ý tưởng của riêng mình.', 'Art & creative corner', 'Where children freely make, experiment and express their own ideas.' ],
		[ 'campus/07-dining.jpg', '07', 'DINING', 'Khu vực ăn uống', 'Không gian dùng bữa thoải mái, sạch sẽ và thân thiện với trẻ.', 'Dining area', 'A comfortable, clean and child-friendly place to share meals.' ],
		[ 'campus/08-play.jpg', '08', 'PLAY AREA', 'Khu vận động & vui chơi', 'Vận động trong nhà giúp con phát triển thể chất, khả năng phối hợp và sự tự tin.', 'Active play area', 'Indoor movement that builds strength, coordination and confidence.' ],
	];

	$glance = [
		[ 'map-pin', 'VỊ TRÍ', 'Thảo Điền, TP. Hồ Chí Minh', 'LOCATION', 'Thao Dien, Ho Chi Minh City' ],
		[ 'arrows-out', 'KHUÔN VIÊN', 'Hơn 2.000 m²', 'CAMPUS', 'Over 2,000 m²' ],
		[ 'shield-check', 'AN TOÀN', 'An ninh và kiểm soát ra vào', 'SAFETY', 'Security & access control' ],
		[ 'users-three', 'QUY MÔ LỚP', 'Tối đa 16 trẻ/lớp', 'CLASS SIZE', 'Max. 16 children per class' ],
		[ 'tree', 'NGOÀI TRỜI', 'Sân chơi rộng với cây xanh', 'OUTDOORS', 'Spacious, green playground' ],
		[ 'books', 'TIỆN ÍCH', 'Thư viện · Art Studio · Bếp ăn · Phòng đa năng', 'FACILITIES', 'Library · Art Studio · Kitchen · Multi-purpose room' ],
	];

	$educators = [
		[ 'edu/01-an.jpg', '01', 'FOUNDER & PRINCIPAL', 'Ms. An Nguyen', 'Một nhà giáo dục tận tâm với mong muốn xây dựng một môi trường nơi mỗi đứa trẻ được nhìn nhận, tôn trọng và tin tưởng.', 'A devoted educator who set out to build a place where every child is seen, respected and trusted.', '“Every child has the potential to become something wonderful.”', [ 'Child-first education', 'Warm leadership', 'Community' ] ],
		[ 'edu/02-linh.jpg', '02', 'ACADEMIC DIRECTOR', 'Ms. Linh Tran', 'Có nền tảng chuyên môn về giáo dục mầm non, phụ trách đảm bảo chương trình và phương pháp giảng dạy tại ELIO luôn phù hợp với sự phát triển của trẻ.', 'With a professional background in early childhood education, she ensures ELIO’s curriculum and teaching methods always match how children develop.', '“Good teaching begins with understanding the child.”', [ 'Evidence-based practice', 'Holistic development', 'Continuous improvement' ] ],
		[ 'edu/03-mai.jpg', '03', 'LEAD TEACHER', 'Ms. Mai Pham', 'Nhẹ nhàng, kiên nhẫn và giàu tính sáng tạo; luôn tìm cách biến những khoảnh khắc đời thường thành cơ hội học tập có ý nghĩa.', 'Gentle, patient and endlessly creative, she turns everyday moments into meaningful chances to learn.', '“When children feel understood, they become ready to explore.”', [ 'Patient & caring', 'Creative learning', 'Strong relationships' ] ],
		// The photo file is named 04-james.jpg; the teacher is Ms. Alice Becca.
		[ 'edu/04-james.jpg', '04', 'INTERNATIONAL TEACHER', 'Ms. Alice Becca', 'Mang tình yêu dành cho khám phá và giao lưu văn hóa vào lớp học, giúp trẻ làm quen với tiếng Anh một cách tự nhiên thông qua vui chơi và giao tiếp.', 'Brings a love of discovery and cultural exchange into the classroom, helping children pick up English naturally through play and conversation.', '“Language is a bridge to connect with the world.”', [ 'Natural language learning', 'Cultural awareness', 'Confidence to communicate' ] ],
	];

	$values = [
		[ 'heart', 'Mỗi đứa trẻ đều độc đáo', 'Tôn trọng nhịp độ, tính cách và sở thích riêng của từng trẻ.', 'Every child is unique', 'We respect each child’s own pace, personality and interests.' ],
		[ 'leaf', 'Môi trường học tập tích cực', 'An toàn, ấm áp và giàu cảm hứng để con tự tin là chính mình.', 'A positive learning environment', 'Safe, warm and inspiring, so children feel confident being themselves.' ],
		[ 'circle', 'Học qua trải nghiệm', 'Quan sát, khám phá và tự mình tìm ra câu trả lời.', 'Learning by experience', 'Observing, exploring and finding the answers for themselves.' ],
		[ 'sun', 'Đồng hành như một cộng đồng', 'Gia đình, giáo viên và nhà trường cùng nhau hỗ trợ hành trình lớn khôn của trẻ.', 'Growing together as a community', 'Families, teachers and the school support each child’s journey together.' ],
	];

	$parents = [
		[ '01', 'BEFORE SCHOOL', 'Bắt đầu cùng nhau', 'Tư vấn · Tham quan · Làm quen', 'Starting together', 'Consultation · Tour · Settling in' ],
		[ '02', 'DURING SCHOOL', 'Kết nối mỗi ngày', 'Trao đổi · Cập nhật · Đồng hành', 'Connected every day', 'Conversations · Updates · Support' ],
		[ '03', 'BEYOND CLASSROOM', 'Cùng con lớn lên', 'Workshop · Sự kiện · Hội thảo phụ huynh', 'Growing together', 'Workshops · Events · Parent talks' ],
	];

	$fees = [
		[ 'Nhà trẻ', '12–24 tháng', '25.000.000 VNĐ/tháng', 'Nursery', '12–24 months', '25,000,000 VND/month' ],
		[ 'Mẫu giáo nhỏ', '2–3 tuổi', '22.000.000 VNĐ/tháng', 'Pre-K', '2–3 years', '22,000,000 VND/month' ],
		[ 'Mẫu giáo lớn', '4–5 tuổi', '22.000.000 VNĐ/tháng', 'Kindergarten', '4–5 years', '22,000,000 VND/month' ],
	];

	$visit = [
		[ 'buildings', 'KHÁM PHÁ KHÔNG GIAN', elio_nb( 'Xem lớp_học, khu vui_chơi và môi_trường học_tập.' ), 'EXPLORE THE CAMPUS', 'See classrooms, play areas and the learning environment.' ],
		[ 'users', 'GẶP GỠ ĐỘI NGŨ', elio_nb( 'Làm_quen với những người sẽ đồng_hành cùng_con.' ), 'MEET THE TEAM', 'Get to know the people who will walk beside your child.' ],
		[ 'question', 'GIẢI ĐÁP THẮC MẮC', elio_nb( 'Tìm_hiểu chương_trình, đời_sống hằng_ngày và tuyển_sinh.' ), 'ASK US ANYTHING', 'Learn about the programme, daily life and admissions.' ],
	];

	return [
		't'         => $t,
		'quick'     => array_map( fn( $q ) => [ 'icon' => $icon( $q[0] ), 'v' => $en ? $q[3] : $q[1], 'k' => $en ? $q[4] : $q[2] ], $quick ),
		'pillars'   => array_map( fn( $p ) => [ 'n' => $p[0], 'en' => $p[1], 'icon' => $icon( $p[2] ), 'title' => $en ? $p[6] : $p[3], 'body' => $en ? $p[7] : $p[4], 'key' => $en ? $p[8] : $p[5] ], $pillars ),
		'day'       => array_map( fn( $d ) => [ 'src' => elio_img( $d[0] ), 'pos' => $d[1], 'tint' => $d[2], 't' => $d[3], 'name' => $en ? $d[7] : $d[4], 'part' => $en ? $d[8] : $d[5], 'body' => $en ? $d[9] : $d[6] ], $day ),
		'nutri'     => array_map( fn( $n ) => [ 'icon' => $icon( $n[0] ), 'tint' => $n[1], 'title' => $en ? $n[4] : $n[2], 'body' => $en ? $n[5] : $n[3] ], $nutri ),
		'polaroids' => array_map( fn( $p ) => [ 'src' => elio_img( $p[0] ), 'place' => $p[1], 'cap' => $en ? $p[3] : $p[2] ], $polaroids ),
		'approach'  => array_map( fn( $a ) => [ 'n' => $a[0], 'en' => $a[1], 'icon' => $icon( $a[2] ), 'tint' => $a[3], 'ink' => $a[4], 'title' => $en ? $a[8] : $a[5], 'body' => $en ? $a[9] : $a[6], 'chips' => $en ? $a[10] : $a[7] ], $approach ),
		'grow'      => array_map( fn( $g ) => [ 'n' => $g[0], 'en' => $g[1], 'title' => $en ? $g[5] : $g[2], 'body' => $en ? $g[6] : $g[3], 'items' => $en ? $g[7] : $g[4] ], $grow ),
		'campus'    => array_map( fn( $c ) => [ 'src' => elio_img( $c[0] ), 'n' => $c[1], 'en' => $c[2], 'title' => $en ? $c[5] : $c[3], 'body' => $en ? $c[6] : $c[4] ], $campus ),
		'glance'    => array_map( fn( $g ) => [ 'icon' => $g[0], 'k' => $en ? $g[3] : $g[1], 'v' => $en ? $g[4] : $g[2] ], $glance ),
		'educators' => array_map( fn( $e ) => [ 'src' => elio_img( $e[0] ), 'n' => $e[1], 'role' => $e[2], 'name' => $e[3], 'who' => $en ? $e[5] : $e[4], 'belief' => $e[6], 'brings' => $e[7] ], $educators ),
		'teamChips' => $en ? [ 'Caring', 'Professional', 'Trusted' ] : [ 'Tận tâm', 'Chuyên nghiệp', 'Đáng tin cậy' ],
		'values'    => array_map( fn( $v ) => [ 'icon' => $v[0], 'title' => $en ? $v[3] : $v[1], 'body' => $en ? $v[4] : $v[2] ], $values ),
		'parents'   => array_map( fn( $p ) => [ 'n' => $p[0], 'en' => $p[1], 'title' => $en ? $p[4] : $p[2], 'tags' => $en ? $p[5] : $p[3] ], $parents ),
		'fees'      => array_map( fn( $f ) => [ 'p' => $en ? $f[3] : $f[0], 'age' => $en ? $f[4] : $f[1], 'fee' => $en ? $f[5] : $f[2] ], $fees ),
		'visit'     => array_map( fn( $v ) => [ 'icon' => $v[0], 'k' => $en ? $v[3] : $v[1], 'v' => $en ? $v[4] : $v[2] ], $visit ),
	];
}

function elio_copy_vi() {
	return [
		'valH'      => 'NHỮNG GIÁ TRỊ CHÚNG TÔI CÙNG CHIA SẺ',
		'teamAlt'   => 'Đội ngũ giáo viên và nhân viên ELIO Preschool',
		'teamK'     => 'THE ELIO TEAM',
		'teamCap'   => 'Một đội ngũ, một mục tiêu: con được an toàn và hạnh phúc.',
		'teamH'     => 'ĐỘI NGŨ ĐỒNG HÀNH CÙNG CON',
		'grH1'      => 'Con sẽ phát triển',
		'grH2'      => 'điều gì?',
		'grP'       => 'Tại ELIO, mỗi trải nghiệm hằng ngày đều được thiết kế để nuôi dưỡng những năng lực quan trọng, giúp con sẵn sàng cho hành trình học tập và cuộc sống trong tương lai.',
		'grDev'     => 'CON PHÁT TRIỂN',
		'nuH1'      => 'Dinh dưỡng',
		'nuH2'      => 'cho một khởi đầu tốt',
		'nuP'       => 'Thực đơn tại ELIO được xây dựng cân bằng, đa dạng và phù hợp với nhu cầu dinh dưỡng của trẻ mầm non.',
		'nuCta'     => 'XEM THỰC ĐƠN →',
		'nuAlt'     => 'Bé ăn trưa tại ELIO',
		'nuBadge'   => 'Nấu mới tại bếp trường mỗi ngày',
		'heroAlt'   => 'Cô giáo và các bé Elio khám phá thiên nhiên',
		'heroKicker' => 'TRƯỜNG MẦM NON QUỐC TẾ · THẢO ĐIỀN',
		'h1a'       => 'Nơi con lớn lên với sự ',
		'h1b'       => 'tò mò, tự tin',
		'h1c'       => ' và ',
		'h1d'       => 'bản sắc riêng.',
		'heroP'     => 'Một môi trường mầm non quốc tế, nơi trẻ được an toàn khám phá, tự do thể hiện và từng ngày mở rộng thế giới của mình.',
		'heroCta'   => 'ĐẶT LỊCH THAM QUAN →',
		'video'     => '▶ XEM VIDEO',
		'badge'     => 'Tối đa 16 trẻ/lớp',
		'whyH'      => 'Một khởi đầu sáng hơn cho những điều con sẽ trở thành.',
		'whyP'      => 'Tại ELIO, chúng tôi không vội định hình một đứa trẻ. Chúng tôi tạo ra môi trường để con được khám phá, được thử, được sai và từng bước trở thành phiên bản của chính mình.',
		'dayH'      => 'Một ngày tại ELIO',
		'dayP'      => 'Những trải nghiệm nhỏ, tạo nên một khởi đầu thật lớn.',
		'quote'     => '“Con thích nhất được chơi ngoài trời với các bạn!”',
		'quoteBy'   => '— Một bạn nhỏ tại ELIO',
		'apH'       => 'ELIO giáo dục trẻ như thế nào?',
		'campusH'   => 'Không gian ELIO',
		'eduH'      => 'Những người đồng hành cùng con',
		'eduP'      => 'Tại ELIO, giáo viên không chỉ là người dạy. Họ là những người quan sát, định hướng và đồng hành, giúp mỗi đứa trẻ khám phá năng lực của chính mình.',
		'parH'      => "Đồng hành cùng phụ\u{00A0}huynh",
		'parP'      => 'Tại ELIO, gia đình và nhà trường cùng là một phần trong hành trình lớn lên của con.',
		'feeK'      => 'HỌC PHÍ',
		'feeH'      => 'Đầu tư cho một tương lai vững vàng',
		'feeP'      => 'Học phí thực tế có thể thay đổi theo chương trình và chính sách tuyển sinh từng thời điểm.',
		'feeA'      => 'XEM CHI TIẾT HỌC PHÍ →',
		'tourH'     => 'Hãy đến và cảm nhận ELIO',
		'tourP'     => 'Cách tốt nhất để hiểu ELIO là trực tiếp trải nghiệm nơi đây.',
		'tourAlt'   => 'Lối vào trường mầm non ELIO',
		'newsK'     => 'TIN TỨC',
		'newsH'     => 'Câu chuyện mới tại ELIO',
		'newsA'     => 'XEM TẤT CẢ BÀI VIẾT →',
	];
}

function elio_copy_en() {
	return [
		'valH'      => 'THE VALUES WE SHARE',
		'teamAlt'   => 'The ELIO Preschool teachers and staff',
		'teamK'     => 'THE ELIO TEAM',
		'teamCap'   => 'One team, one goal: children who feel safe and happy.',
		'teamH'     => 'THE TEAM BESIDE YOUR CHILD',
		'grH1'      => 'What will your child',
		'grH2'      => 'grow?',
		'grP'       => 'At ELIO, every daily experience is designed to nurture essential abilities, preparing children for their learning journey and the life ahead.',
		'grDev'     => 'YOUR CHILD GROWS',
		'nuH1'      => 'Nutrition',
		'nuH2'      => 'for a good beginning',
		'nuP'       => 'Meals at ELIO are balanced, varied and planned around the nutritional needs of young children.',
		'nuCta'     => 'SEE THE MENU →',
		'nuAlt'     => 'A child enjoying lunch at ELIO',
		'nuBadge'   => 'Freshly cooked in our kitchen daily',
		'heroAlt'   => 'An Elio teacher and children exploring nature',
		'heroKicker' => 'INTERNATIONAL PRESCHOOL · THAO DIEN',
		'h1a'       => 'Where children grow with ',
		'h1b'       => 'curiosity, confidence',
		'h1c'       => ' and ',
		'h1d'       => 'a voice of their own.',
		'heroP'     => 'An international preschool where children explore safely, express themselves freely and widen their world a little more every day.',
		'heroCta'   => 'BOOK A SCHOOL TOUR →',
		'video'     => '▶ WATCH VIDEO',
		'badge'     => 'Max. 16 children per class',
		'whyH'      => 'A brighter beginning for everything your child will become.',
		'whyP'      => 'At ELIO, we are in no hurry to shape a child. We create a place where children can explore, try, make mistakes and, step by step, become who they truly are.',
		'dayH'      => 'A day at ELIO',
		'dayP'      => 'Small moments that add up to a big beginning.',
		'quote'     => '“I love playing outside with my friends the most!”',
		'quoteBy'   => '— A little one at ELIO',
		'apH'       => 'How does ELIO teach?',
		'campusH'   => 'The ELIO campus',
		'eduH'      => 'The people beside your child',
		'eduP'      => 'At ELIO, teachers are not simply instructors. They are observers, facilitators and trusted adults who help children discover their own capabilities.',
		'parH'      => 'Partnering with parents',
		'parP'      => 'At ELIO, family and school are both part of a child’s journey of growing up.',
		'feeK'      => 'TUITION',
		'feeH'      => 'An investment in a steady future',
		'feeP'      => 'Actual tuition may vary by programme and current admissions policy.',
		'feeA'      => 'SEE FULL TUITION →',
		'tourH'     => 'Come and feel ELIO for yourself',
		'tourP'     => 'The best way to understand ELIO is to experience it in person.',
		'tourAlt'   => 'The entrance of ELIO Preschool',
		'newsK'     => 'NEWS',
		'newsH'     => 'Latest stories from ELIO',
		'newsA'     => 'SEE ALL POSTS →',
	];
}
