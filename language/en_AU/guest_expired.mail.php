<?php 
// WARNING, this is a read only file created by import scripts
// WARNING
// WARNING,  Changes made to this file will be clobbered
// WARNING
// WARNING,  Please make changes on poeditor instead of here
// 
// 
?>
subject: Guest voucher expired

{alternative:plain}

Hello,

A guest voucher from {guest.user_email} has expired.

Best regards,
{cfg:site_name}

{alternative:html}

<p>
    Hello,
</p>

<p>
    A guest voucher from <a href="mailto:{guest.user_email}">{guest.user_email}</a> has expired.
</p>

<p>
    Best regards,<br />
    {cfg:site_name}
</p>
