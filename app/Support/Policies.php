<?php

namespace App\Support;

/**
 * The wording the shop stands behind when it has not written its own.
 *
 * These are not filler. An Indian payment gateway will not approve a merchant
 * account without a returns policy, a delivery policy, terms and a privacy
 * notice that can actually be read at a web address, and a shop that opens
 * with four empty pages fails that check on the first day.
 *
 * Everything here is written to be true of this shop as built — the dispatch
 * time, the free-delivery figure and the refund route all come from the
 * settings the shop actually uses, so the pages cannot promise one thing while
 * checkout does another. The owner can replace any of it in Settings.
 */
class Policies
{
    public static function returns(): string
    {
        $name = Shop::name();

        return <<<TEXT
        We want you to be happy with what arrives. If you are not, tell us within **seven days** of delivery and we will put it right.

        **What we can take back**

        A saree that is unworn, unwashed and still has its tags, in the condition it reached you, with the blouse piece. Please keep the packaging until you are sure.

        **What we cannot take back**

        A saree that has been worn, washed, cut, stitched or altered in any way — including a blouse stitched from the attached piece. Pieces bought during a clearance sale are sold as seen.

        **How to start a return**

        Write to {$name} at the email address on our contact page, or send a message on WhatsApp, with your order number and a photograph of the piece. We will answer within two working days and arrange a pick-up where the courier serves your pincode. Where they do not, we will ask you to post it back and will refund the postage.

        **Your money back**

        Once the piece reaches us and has been checked — usually two working days — the refund goes back the way you paid. A card, UPI or net-banking payment is returned to the same account and takes five to seven working days to appear, which is the bank's timing and not ours. A cash-on-delivery order is refunded by bank transfer to an account you give us.

        The delivery charge is refunded only where the piece was faulty or we sent the wrong one.

        **Something damaged or wrong**

        Tell us within 48 hours of delivery and send a photograph. We will replace it or refund it in full, including the delivery charge, and arrange the pick-up ourselves. Please photograph the parcel before opening it if it looks tampered with — it makes the claim with the courier straightforward.

        **Exchanges**

        A different shade or a different design: tell us within seven days and we will exchange it once, free of charge, as long as the piece is unworn and the one you want is in stock.
        TEXT;
    }

    public static function shipping(): string
    {
        $days = Shop::dispatchDays();
        $free = Shop::money(Shop::freeShippingFrom());
        $flat = Shop::money(Shop::flatShipping());

        $cod = Shop::codOn()
            ? "**Cash on delivery** is available across most of India. Where we charge for it, the amount is shown at checkout before you place the order, never after.\n\n"
            : '';

        return <<<TEXT
        **When it leaves us**

        Every order is checked, folded and posted within **{$days} working days**. Orders placed on a Sunday or a public holiday go out on the next working day.

        **What it costs**

        Delivery is **free on orders over {$free}**. Below that it is {$flat}, and the exact amount for your pincode is shown at checkout before you pay. Some remote pincodes cost more; that too is shown before you pay, never after.

        {$cod}**How long it takes**

        Two to four working days within South India, three to seven across the rest of the country, and six to twelve for the North East, the islands and other remote pincodes. These are the courier's working days, not ours.

        **Tracking it**

        You will get an email with the courier's name and tracking number on the day your parcel leaves us. You can also look it up at any time with your order number on our tracking page.

        **If nobody is in**

        The courier tries three times. After the third attempt the parcel comes back to us, and we will write to you to arrange sending it again. A second delivery attempt after a returned parcel is charged at cost.

        **Outside India**

        We are not shipping overseas at the moment. Write to us anyway — if there are enough of you asking from one country, we will.
        TEXT;
    }

    public static function terms(): string
    {
        $name = Shop::name();
        $email = Shop::email();

        return <<<TEXT
        These terms govern your use of this website and anything you buy from it. By placing an order you accept them.

        **Who we are**

        {$name}, selling handwoven sarees in India. You can reach us at {$email} or on the telephone number on our contact page.

        **The sarees**

        Everything here is handwoven, which means no two pieces are identical. Slight irregularities in the weave are a feature of handloom work and not a defect. We photograph every piece in daylight and do not retouch the colour, but screens differ, and a shade may look a little different on your phone from how it looks in your hand. If it is not what you expected, our returns policy covers you.

        **Prices and availability**

        Prices are in Indian rupees and include all taxes. We may change a price at any time, but never after you have placed an order. Where a piece sells out between your adding it to your bag and placing the order, we will tell you and refund you in full.

        If a saree is listed at an obviously wrong price — a decimal point in the wrong place — we may cancel the order and refund you rather than honour it. We will write to you first.

        **Your order**

        An order is an offer to buy. The contract is made when we confirm it. We may decline an order, and will say why, where we cannot deliver to the pincode, where the piece is no longer available, or where we have reason to believe the order is not genuine.

        **Paying**

        Card, UPI, net banking and wallet payments are handled by Razorpay. We never see or store your card details. Cash-on-delivery orders are paid to the courier at the door.

        **Offers and codes**

        An offer applies itself in your bag — you need type nothing. A coupon code is typed at checkout. Offers and codes cannot be combined unless we say so, may be withdrawn at any time, and have no cash value.

        **Your account**

        You do not need an account to buy. If you make one, keep your password to yourself; you are responsible for what is done through it. Tell us at once if you think somebody else has it.

        **Our words and pictures**

        The photographs, text and design on this site are ours. Please do not copy them for a competing shop. You are very welcome to share a link, or to post a photograph of a saree you bought from us.

        **What we are not responsible for**

        We are responsible for the saree reaching you as described. We are not responsible for a delay caused by the courier, by weather, or by anything else outside our control, nor for any loss beyond the value of the order itself.

        Nothing here limits your rights under the Consumer Protection Act, 2019.

        **Disagreements**

        Indian law applies, and the courts of Telangana have jurisdiction. Before any of that, please write to us — nearly everything is settled with one email.

        **Changes**

        We may change these terms. The version on this page when you place an order is the one that governs it.
        TEXT;
    }

    public static function privacy(): string
    {
        $name = Shop::name();
        $email = Shop::email();

        return <<<TEXT
        The short version: we collect what we need to send you a saree, we do not sell it to anybody, and you can ask us to delete it.

        **What we collect**

        When you order: your name, email, telephone number and delivery address. When you make an account: the same, plus a password we store only as a one-way hash — we cannot read it, and neither can anyone who steals the database.

        When you simply visit: the pages you looked at and roughly where you are, through Google Analytics and, if we are advertising, a Meta pixel. This is counted in aggregate; it does not tell us who you are.

        **What we do not collect**

        Your card details. Payments go to Razorpay, who are certified to handle them; the card number never reaches this website and we could not see it if we wanted to.

        **Why we have it**

        To send you your order, to tell you where it is, to answer you when you write, to keep the shop's accounts as the law requires, and to understand which pages are useful. Nothing else.

        **Who else sees it**

        Only those who have to: the courier (your name, address and telephone number, so they can deliver it), Razorpay (to take the payment), and our email provider (to send you your confirmation). None of them may use it for anything else, and we sell your details to nobody, ever.

        **Emails**

        We email you about your own order because you ordered. We will only send you anything else if you asked for it, and every such email has an unsubscribe link that works.

        **Cookies**

        One to remember what is in your bag, one to keep you signed in, and — where they are set up — Google Analytics and Meta cookies that count visits. Blocking the analytics cookies in your browser does not stop you shopping.

        **How long we keep it**

        Order records for eight years, because tax law requires it. An account for as long as you want one.

        **Your say**

        Write to {$email} and ask to see what we hold about you, to have it corrected, or to have it deleted. We will answer within thirty days. Order records we are obliged to keep are the one thing we cannot delete, and we will tell you so plainly.

        **Keeping it safe**

        The site is served over HTTPS, passwords are hashed, and only {$name}'s own staff can open the admin. No system is perfect; if anything ever goes wrong we will tell you rather than hope you do not notice.

        **Changes**

        If we change this, the date at the bottom of the page changes with it.
        TEXT;
    }

    public static function story(): string
    {
        $name = Shop::name();

        return <<<TEXT
        {$name} began the way most small saree shops do: with more pieces than anyone needed, bought because they were too good to leave on the loom.

        We buy direct from weavers, a few pieces at a time. That is why there are a dozen designs here and not a thousand — every one has been seen, handled and chosen by somebody who will have to look you in the eye if it disappoints you.

        Each saree is photographed in daylight, on the day it arrives, without retouching. What you see is the piece you will be sent, not a sample of it. Before it is folded it is looked over thread by thread, and anything with a pulled thread goes back.

        Handloom work is not uniform, and we would not want it to be. Two sarees from the same weaver in the same week will differ a little. That is the point of it.

        If something is not right, tell us. We would rather take a saree back than have it sit unworn in your cupboard.
        TEXT;
    }
}
