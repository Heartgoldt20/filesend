<?php 
// WARNING, this is a read only file created by import scripts
// WARNING
// WARNING,  Changes made to this file will be clobbered
// WARNING
// WARNING,  Please make changes on poeditor instead of here
// 
// 
?>
විෂය: ආගන්තුක වවුචරය ලැබුණි
විෂය: {guest.subject}

{alternative:plain}

හිතවත් මහත්මයා හෝ මැතිණියනි,

කරුණාකර {cfg:site_name} වෙත ප්‍රවේශය ලබා දෙන වවුචරයක් පහතින් සොයා ගන්න. ඔබට මෙම වවුචරය භාවිතා කර එක් ගොනු කට්ටලයක් උඩුගත කිරීමට සහ එය පුද්ගලයන් පිරිසකට බාගත කිරීම සඳහා ලබා ගත හැක.

නිකුත් කරන්නා: {guest.user_email}
වවුචර් සබැඳිය: {guest.upload_link}

{if:guest.does_not_expire}
මෙම වවුචරය කල් ඉකුත් නොවේ.
{else}
වවුචරය {date:guest.expires} වන තෙක් ලබා ගත හැකි අතර ඉන් පසුව එය ස්වයංක්‍රීයව මැකෙනු ඇත.
{endif}

{if:guest.message}පෞද්ගලික පණිවිඩය {guest.user_email} වෙතින්: {guest.message}{endif}

සුභ පතමින්,
{cfg:site_name}

{alternative:html}

<p>
    හිතවත් මහත්මයා හෝ මැතිණියනි,
</p>

<p>
    කරුණාකර <a href="{cfg:site_url}">{cfg:site_name}</a> වෙත ප්‍රවේශය ලබා දෙන වවුචරයක් පහතින් සොයා ගන්න. ඔබට මෙම වවුචරය භාවිතා කර එක් ගොනු කට්ටලයක් උඩුගත කිරීමට සහ එය පුද්ගලයන් පිරිසකට බාගත කිරීම සඳහා ලබා ගත හැක.
</p>

<table rules="rows">
    <thead>
        <tr>
            <th colspan="2">Voucher details</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Issuer</td>
            <td><a href="mailto:{guest.user_email}">{guest.user_email}</a></td>
        </tr>
        <tr>
            <td>Voucher link</td>
            <td><a href="{guest.upload_link}">{guest.upload_link}</a></td>
        </tr>
        <tr>
{if:guest.does_not_expire}
            <td colspan="2">This invitation does not expire</td>
{else}
            <td>Valid until</td>
            <td>{date:guest.expires}</td>
{endif}

        </tr>
    </tbody>
</table>


{if:guest.message}
<p>
    {guest.user_email} වෙතින් පුද්ගලික පණිවිඩය:
</p>
<p class="message">
    {guest.message}
</p>
{endif}

<p>
    සුභ පැතුම්,<br />
    {cfg:site_name}
</p>