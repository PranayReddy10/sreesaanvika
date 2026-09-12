<?php
/**
 * The four policy pages: structure, highlights and default copy.
 *
 * The text is written for an Indian direct-to-consumer store and reflects the
 * figures set in Customizer → Policies & Legal, so the return window, the
 * shipping threshold and the COD limit only need changing in one place. It is
 * a starting draft, not legal advice — have a lawyer read it before launch.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * The policy pages the theme ships.
 *
 * @return array
 */
function ss_legal_pages() {
	return array(
		'privacy-policy'       => array( 'title' => __( 'Privacy Policy', 'sreesaanvika' ) ),
		'terms-and-conditions' => array( 'title' => __( 'Terms & Conditions', 'sreesaanvika' ) ),
		'shipping-policy'      => array( 'title' => __( 'Shipping Policy', 'sreesaanvika' ) ),
		'returns'              => array( 'title' => __( 'Return & Refund Policy', 'sreesaanvika' ) ),
	);
}

/**
 * Give every H2 in the content an id, and return the list for the contents rail.
 *
 * @param string $html Rendered content.
 * @return array{0:string,1:array} Content with anchors, and the heading list.
 */
function ss_legal_anchor_headings( $html ) {
	$toc  = array();
	$seen = array();

	$html = preg_replace_callback(
		'/<h2(?![^>]*\bid=)([^>]*)>(.*?)<\/h2>/is',
		function ( $matches ) use ( &$toc, &$seen ) {
			$title = trim( wp_strip_all_tags( $matches[2] ) );

			if ( '' === $title ) {
				return $matches[0];
			}

			$slug = sanitize_title( $title );
			$slug = $slug ? $slug : 'section';

			// Keep ids unique if two headings share a title.
			if ( isset( $seen[ $slug ] ) ) {
				$seen[ $slug ]++;
				$slug .= '-' . $seen[ $slug ];
			} else {
				$seen[ $slug ] = 1;
			}

			$toc[] = array(
				'id'    => $slug,
				'title' => $title,
			);

			return '<h2 id="' . esc_attr( $slug ) . '"' . $matches[1] . '>' . $matches[2] . '</h2>';
		},
		$html
	);

	return array( $html, $toc );
}

/**
 * The facts most readers actually came for, shown above the document.
 *
 * @param string $slug Page slug.
 * @return array
 */
function ss_legal_highlights( $slug ) {
	$days      = absint( ss_option( 'returns_window_days' ) );
	$threshold = number_format_i18n( (float) ss_option( 'free_ship_threshold' ) );
	$flat      = number_format_i18n( (float) ss_option( 'flat_ship_rate' ) );
	$cod       = number_format_i18n( (float) ss_option( 'cod_limit' ) );

	$sets = array(
		'returns'         => array(
			array( 'icon' => 'refresh', 'title' => sprintf( /* translators: %d: days */ __( '%d-day window', 'sreesaanvika' ), $days ), 'text' => __( 'From the day your parcel arrives', 'sreesaanvika' ) ),
			array( 'icon' => 'truck', 'title' => __( 'Free pickup', 'sreesaanvika' ), 'text' => __( 'Wherever our courier reaches', 'sreesaanvika' ) ),
			array( 'icon' => 'clock', 'title' => __( '5–7 days', 'sreesaanvika' ), 'text' => __( 'For the refund to land', 'sreesaanvika' ) ),
			array( 'icon' => 'scissors', 'title' => __( 'Some exceptions', 'sreesaanvika' ), 'text' => __( 'Stitched and altered pieces', 'sreesaanvika' ) ),
		),
		'shipping-policy' => array(
			array( 'icon' => 'truck', 'title' => sprintf( /* translators: %s: amount */ __( 'Free over ₹%s', 'sreesaanvika' ), $threshold ), 'text' => sprintf( /* translators: %s: amount */ __( 'Flat ₹%s below that', 'sreesaanvika' ), $flat ) ),
			array( 'icon' => 'clock', 'title' => __( '2–7 days', 'sreesaanvika' ), 'text' => __( 'Metro cities to the rest of India', 'sreesaanvika' ) ),
			array( 'icon' => 'tag', 'title' => sprintf( /* translators: %s: amount */ __( 'COD to ₹%s', 'sreesaanvika' ), $cod ), 'text' => __( 'In serviceable PIN codes', 'sreesaanvika' ) ),
			array( 'icon' => 'globe', 'title' => __( 'We ship worldwide', 'sreesaanvika' ), 'text' => __( '7–14 days, quoted at checkout', 'sreesaanvika' ) ),
		),
		'privacy-policy'  => array(
			array( 'icon' => 'lock', 'title' => __( 'We never sell your data', 'sreesaanvika' ), 'text' => __( 'Not to anyone, at any price', 'sreesaanvika' ) ),
			array( 'icon' => 'shield', 'title' => __( 'Encrypted in transit', 'sreesaanvika' ), 'text' => __( 'And we never store card numbers', 'sreesaanvika' ) ),
			array( 'icon' => 'user', 'title' => __( 'Your data, your call', 'sreesaanvika' ), 'text' => __( 'Ask us to export or delete it', 'sreesaanvika' ) ),
		),
		'terms-and-conditions' => array(
			array( 'icon' => 'check-circle', 'title' => __( 'Plain terms', 'sreesaanvika' ), 'text' => __( 'No clauses designed to trip you up', 'sreesaanvika' ) ),
			array( 'icon' => 'tag', 'title' => __( 'Prices include GST', 'sreesaanvika' ), 'text' => __( 'What you see is what you pay', 'sreesaanvika' ) ),
			array( 'icon' => 'headset', 'title' => __( 'A named grievance officer', 'sreesaanvika' ), 'text' => __( 'Reachable, and required by law', 'sreesaanvika' ) ),
		),
	);

	return isset( $sets[ $slug ] ) ? $sets[ $slug ] : array();
}

/**
 * Placeholders substituted into the default copy.
 *
 * @return array
 */
function ss_legal_tokens() {
	return array(
		'{business}'     => ss_option( 'legal_entity' ) ? ss_option( 'legal_entity' ) : get_bloginfo( 'name' ),
		'{site}'         => get_bloginfo( 'name' ),
		'{domain}'       => wp_parse_url( home_url(), PHP_URL_HOST ),
		'{email}'        => ss_option( 'footer_email' ),
		'{phone}'        => ss_option( 'footer_phone' ),
		'{address}'      => str_replace( "\n", ', ', trim( (string) ss_option( 'footer_address' ) ) ),
		'{hours}'        => ss_option( 'footer_hours' ),
		'{days}'         => (string) absint( ss_option( 'returns_window_days' ) ),
		'{threshold}'    => number_format_i18n( (float) ss_option( 'free_ship_threshold' ) ),
		'{flat}'         => number_format_i18n( (float) ss_option( 'flat_ship_rate' ) ),
		'{cod}'          => number_format_i18n( (float) ss_option( 'cod_limit' ) ),
		'{jurisdiction}' => ss_option( 'legal_jurisdiction' ),
		'{officer}'      => ss_option( 'grievance_officer' ),
		'{gstin}'        => ss_option( 'legal_gstin' ),
	);
}

/**
 * Substitute the placeholders in a block of copy.
 *
 * @param string $text Copy with {tokens}.
 * @return string
 */
function ss_legal_fill( $text ) {
	$tokens = ss_legal_tokens();

	return strtr( $text, $tokens );
}

/**
 * Default copy for a policy page.
 *
 * @param string $slug Page slug.
 * @return string HTML with {tokens} already filled.
 */
function ss_legal_body( $slug ) {
	$bodies = array();

	/* ================================================================
	 * Privacy Policy
	 * ============================================================= */
	$bodies['privacy-policy'] = '
<p>{business} ("we", "us") runs the store at {domain}. This policy explains what personal information we collect when you browse or buy from us, why we collect it, who else sees it, and what you can ask us to do with it. We have tried to write it in plain language rather than legalese.</p>

<h2>What we collect</h2>
<p>We only ask for what an order actually needs.</p>
<ul>
<li><strong>Information you give us</strong> — your name, delivery and billing address, email address, phone number, and anything you type into an order note or a message to our team.</li>
<li><strong>Order information</strong> — what you bought, when, the amount, and the delivery status.</li>
<li><strong>Account information</strong> — if you create an account, your saved addresses, wishlist and order history.</li>
<li><strong>Technical information</strong> — your IP address, browser and device type, and the pages you visit on our site. This is collected automatically and is used in aggregate.</li>
</ul>
<p><strong>We never see your full card number.</strong> Card and UPI details are entered on our payment gateway\'s systems and are never stored on our servers.</p>

<h2>Why we use it</h2>
<ul>
<li>To take payment, pack your order and get it to your door.</li>
<li>To send you order confirmations, dispatch alerts and delivery updates.</li>
<li>To handle returns, exchanges, refunds and support requests.</li>
<li>To keep the site working, to spot fraud, and to fix things that break.</li>
<li>To send offers and new-arrival emails — <em>only</em> if you asked for them, and every one of those emails has an unsubscribe link that works.</li>
</ul>

<h2>Who else sees it</h2>
<p>We do not sell your personal information. We never have and we do not intend to. We do share the minimum necessary with the companies that help us run the store:</p>
<ul>
<li><strong>Payment gateways</strong> to process your payment securely.</li>
<li><strong>Courier partners</strong> — your name, address and phone number, so they can deliver the parcel and call you if they cannot find it.</li>
<li><strong>Our email and SMS providers</strong> to send transactional messages.</li>
<li><strong>Our hosting provider</strong>, which stores the site and its database.</li>
</ul>
<p>We may also disclose information where a law, a court order or a government authority requires it, or to protect our rights where someone is defrauding us.</p>

<h2>Cookies</h2>
<p>Cookies are small files your browser stores. We use them to remember what is in your bag, to keep you signed in, to remember your wishlist and comparison list, and to understand which pages people find useful. You can block or delete cookies in your browser settings — the store will still work, but your bag will not survive a page reload.</p>

<h2>How long we keep it</h2>
<p>Order records are kept for as long as tax and accounting law requires. Account information is kept until you ask us to delete the account. Marketing consent is kept until you withdraw it. Technical logs are kept for a short period and then discarded.</p>

<h2>How we protect it</h2>
<p>The site runs over HTTPS, so information sent between your browser and us is encrypted in transit. Access to order data is limited to the people on our team who need it to do their job. No system is perfectly secure, and we will not pretend otherwise — but if a breach ever affects your data, we will tell you.</p>

<h2>Your rights</h2>
<p>Under India\'s Digital Personal Data Protection Act, 2023, and the IT (Reasonable Security Practices) Rules, 2011, you can ask us to:</p>
<ul>
<li>tell you what personal data of yours we hold;</li>
<li>correct anything that is wrong or out of date;</li>
<li>delete your data, where we are not required to keep it;</li>
<li>withdraw a consent you previously gave, such as marketing emails;</li>
<li>nominate someone to exercise these rights on your behalf.</li>
</ul>
<p>Email {email} and we will respond within 30 days. We may need to confirm your identity first, so that nobody else can make these requests about you.</p>

<h2>Children</h2>
<p>Our store is not aimed at children, and we do not knowingly collect data from anyone under 18. If you believe a child has given us personal information, write to us and we will remove it.</p>

<h2>Grievance officer</h2>
<p>As required by Indian law, complaints about how we handle personal data can be addressed to:</p>
<p><strong>{officer}</strong><br />{business}<br />{address}<br />Email: {email}<br />Phone: {phone}<br />Hours: {hours}</p>

<h2>Changes to this policy</h2>
<p>When we change this policy we update the date at the top of this page. If a change materially affects how we use your data, we will say so by email to customers with an account.</p>
';

	/* ================================================================
	 * Terms & Conditions
	 * ============================================================= */
	$bodies['terms-and-conditions'] = '
<p>These terms govern your use of {domain} and any order you place with {business}. By browsing the site or buying from us, you accept them. Please read them — particularly the sections on orders, pricing and liability.</p>

<h2>Who we are</h2>
<p>{business}, {address}. Contact: {email} · {phone}.</p>

<h2>Using the site</h2>
<p>You may browse and shop freely. You may not scrape the site, resell our product photography or descriptions, attempt to break into any part of it, or use it to do anything unlawful. You are responsible for keeping your account password to yourself.</p>

<h2>Product descriptions and images</h2>
<p>We photograph every piece ourselves in daylight and describe it as accurately as we can. Handloom textiles are made by people, not machines: slight irregularities in weave, small variations between dye lots, and minor differences between the photograph and the piece you receive are characteristics of the craft rather than defects. Screens also render colour differently. Where a piece is powerloom, blended, or uses imitation zari, we say so plainly on the product page.</p>

<h2>Prices and payment</h2>
<ul>
<li>All prices are in Indian Rupees and include GST unless stated otherwise.</li>
<li>Prices can change at any time, but a change never affects an order already placed.</li>
<li>We accept UPI, credit and debit cards, netbanking, wallets, EMI on eligible cards, and cash on delivery up to ₹{cod}.</li>
<li>If a price is listed incorrectly because of an obvious error, we will contact you before dispatch and you may cancel for a full refund.</li>
</ul>

<h2>Orders</h2>
<p>Your order is an offer to buy. A contract forms only when we confirm dispatch. We may decline or cancel an order — with a full refund — if the item is out of stock, if we cannot deliver to your PIN code, if payment fails verification, or if we suspect fraud or resale.</p>

<h2>Delivery, returns and refunds</h2>
<p>Delivery is covered by our <a href="' . esc_url( ss_page_url( 'shipping-policy' ) ) . '">Shipping Policy</a>, and returns and refunds by our <a href="' . esc_url( ss_page_url( 'returns' ) ) . '">Return &amp; Refund Policy</a>. Both form part of these terms.</p>

<h2>Intellectual property</h2>
<p>The name {site}, the logo, the site design, the photography and the written descriptions belong to us. You may share links and images on social media with credit. You may not use them commercially, or to sell competing goods, without our written permission.</p>

<h2>Your content</h2>
<p>If you post a review or send us a photograph of yourself in one of our pieces, you keep ownership of it, and you give us permission to display it on the site and our social channels with your first name. Tell us and we will take it down. Reviews that are abusive, fake or unrelated to the product may be removed.</p>

<h2>Liability</h2>
<p>Nothing here limits your rights under the Consumer Protection Act, 2019, and nothing excludes our liability for death, personal injury or fraud caused by us. Beyond that, our liability for any order is limited to the amount you paid for it. We are not liable for indirect losses — for example, a delayed parcel causing you to miss an event — although we will always try hard to prevent that from happening.</p>

<h2>Events outside our control</h2>
<p>We are not responsible for delays caused by things genuinely outside our control: courier strikes, floods, cyclones, curfews, network failures or government restrictions. If one of those happens, we will keep you informed and, where a delay becomes unreasonable, offer you a refund.</p>

<h2>Grievance redressal</h2>
<p>Complaints go to <strong>{officer}</strong> at {email} or {phone}, {hours}. We acknowledge within 48 hours and aim to resolve within 30 days, as the Consumer Protection (E-Commerce) Rules, 2020 require.</p>

<h2>Governing law</h2>
<p>These terms are governed by the laws of India. Disputes are subject to the exclusive jurisdiction of the courts at {jurisdiction}.</p>

<h2>Changes</h2>
<p>We may update these terms. The version that applies to your order is the one published when you placed it.</p>
';

	/* ================================================================
	 * Shipping Policy
	 * ============================================================= */
	$bodies['shipping-policy'] = '
<p>Everything below applies to orders placed on {domain}. If your parcel is late or something looks wrong, contact us before you worry — we can usually tell you exactly where it is.</p>

<h2>Order processing</h2>
<p>Orders are packed within 1–2 business days. Pieces that need fall and pico stitching, or any made-to-order finishing, add roughly two days. We do not dispatch on Sundays or public holidays.</p>

<h2>Delivery times within India</h2>
<ul>
<li><strong>Metro cities</strong> — 2–4 business days after dispatch.</li>
<li><strong>Other cities and towns</strong> — 4–7 business days.</li>
<li><strong>Remote and hill PIN codes</strong> — up to 10 business days.</li>
</ul>
<p>These are estimates from our courier partners, counted from dispatch rather than from when you ordered.</p>

<h2>Shipping charges</h2>
<ul>
<li><strong>Free</strong> on all orders above ₹{threshold}.</li>
<li><strong>Flat ₹{flat}</strong> on orders below that.</li>
<li>Cash on delivery is available up to ₹{cod} in serviceable PIN codes. Enter your PIN code on any product page to check.</li>
</ul>

<h2>International shipping</h2>
<p>We ship worldwide. Rates are calculated at checkout by weight and destination, and delivery usually takes 7–14 business days. Customs duties, import taxes and clearance charges are set by your country and are payable by you on arrival — they are not included in what you pay us, and we have no way to estimate them for you in advance.</p>

<h2>Tracking your parcel</h2>
<p>The moment your order leaves us you will get a tracking link by email and SMS. You can also follow it on our <a href="' . esc_url( ss_page_url( 'track' ) ) . '">order tracking page</a>, or from your account. Tracking can take up to 24 hours to start updating after dispatch.</p>

<h2>Getting the address right</h2>
<p>Please double-check your address and phone number at checkout. We can change an address within 12 hours of the order, before it reaches the courier. After that we cannot. If a parcel is returned to us because the address was wrong or nobody was reachable, we will re-ship it once you cover the return and re-delivery cost.</p>

<h2>Failed and undelivered parcels</h2>
<p>Couriers attempt delivery up to three times. If all three fail the parcel comes back to us, and we refund the order value less the shipping cost actually incurred. For cash-on-delivery orders that are refused at the door, we may ask for prepayment on any future order.</p>

<h2>Damaged or missing parcels</h2>
<p>If a parcel arrives damaged, please photograph it before opening and send us the pictures within 48 hours. If tracking shows delivery but you have not received the parcel, tell us within 48 hours and we will raise it with the courier immediately. We handle these ourselves — you should not have to chase a courier company.</p>

<h2>Delays</h2>
<p>Festivals, weather, strikes and regional restrictions do slow couriers down, particularly in October and November. We will keep you posted if your order is affected, and if a delay becomes unreasonable you can cancel for a full refund.</p>
';

	/* ================================================================
	 * Return & Refund Policy
	 * ============================================================= */
	$bodies['returns'] = '
<p>We would rather you loved the piece than kept something you do not. If it is not right, send it back — the process below is deliberately simple, and reverse pickup is on us.</p>

<h2>The window</h2>
<p>You have <strong>{days} days from the day your parcel is delivered</strong> to raise a return. Tell us within that window and we will take it from there, even if the parcel takes a few more days to reach us.</p>

<h2>Condition</h2>
<p>The piece must come back unworn and unwashed, with its tags attached and in its original packing, along with the invoice. Perfume, deodorant, makeup marks and cooking smells all make a garment unsellable, so please try things on carefully.</p>

<h2>What cannot be returned</h2>
<ul>
<li>Blouses stitched to your measurements, and any garment we altered for you.</li>
<li>Sarees sent for fall and pico stitching at your request.</li>
<li>Pierced jewellery — earrings, nose pins and nose rings — for hygiene reasons.</li>
<li>Items marked <em>final sale</em> on the product page.</li>
<li>Anything returned without tags, or damaged after delivery.</li>
</ul>
<p>None of this applies if the piece arrived damaged, faulty, or is not what you ordered. In that case it comes back regardless, and we cover everything.</p>

<h2>How to return something</h2>
<ol>
<li>Email {email} or call {phone} with your order number and a line about what is wrong. A photograph helps if the item is damaged.</li>
<li>We arrange a free reverse pickup, usually within 2–3 business days, wherever our courier reaches. If yours is not serviceable we will ask you to self-ship and reimburse the postage.</li>
<li>Pack the piece with its tags and invoice, and hand it to the courier.</li>
<li>We check it on arrival — usually within 48 hours — and start the refund.</li>
</ol>

<h2>Refunds</h2>
<ul>
<li>Refunds go back to the original payment method within <strong>5–7 business days</strong> of us receiving and checking the piece.</li>
<li>For cash-on-delivery orders we refund by UPI or bank transfer, so we will ask you for those details.</li>
<li>Shipping charges are refunded when the return is our fault — a damaged, faulty or wrong item. Otherwise the original shipping cost is not refunded.</li>
<li>Bank processing can add a few days at their end, which is outside our control.</li>
</ul>

<h2>Exchanges</h2>
<p>Want a different size or colour rather than your money back? Say so when you raise the return and we will reserve the replacement while the first piece travels back, subject to stock. Exchanges ship free.</p>

<h2>Damaged, faulty or wrong items</h2>
<p>Tell us within 48 hours of delivery with photographs. We will replace the piece or refund it in full, including all shipping, and we will arrange the pickup. You will not be asked to pay anything.</p>

<h2>Cancelling an order</h2>
<p>You can cancel free of charge any time before dispatch — email or call us and we will refund in full. Once a parcel has left us it becomes a return rather than a cancellation.</p>

<h2>If you are unhappy with the outcome</h2>
<p>Escalate to <strong>{officer}</strong> at {email} or {phone}, {hours}. We acknowledge within 48 hours and aim to resolve within 30 days. Your rights under the Consumer Protection Act, 2019 are unaffected by anything in this policy.</p>
';

	if ( ! isset( $bodies[ $slug ] ) ) {
		return '';
	}

	return ss_legal_fill( trim( $bodies[ $slug ] ) );
}
