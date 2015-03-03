subject: Message delivery failure

{alternative:plain}

Dear Sir or Madam,

One or more of your recipients failed to receive your message(s) :

{each:bounces as bounce}
{if:bounce.target_type=="Recipient"}
  - Transfer #{bounce.target.transfer.id} recipient {bounce.target.email} on {datetime:bounce.date}
{endif}{if:bounce.target_type=="Guest"}
  - Guest {bounce.target.email} on {datetime:bounce.date}
{endif}
{endeach}

You may find additionnal details at {cfg:site_url}

Best regards,
{cfg:site_name}

{alternative:html}

<p>
    Dear Sir or Madam,
</p>

<p>
    One or more of your recipients failed to receive your message(s) :
</p>

<ul>
{each:bounces as bounce}
    <li>
    {if:bounce.target_type=="Recipient"}
        Transfer #{bounce.target.transfer.id} recipient {bounce.target.email} on {datetime:bounce.date}
    {endif}{if:bounce.target_type=="Guest"}
        Guest {bounce.target.email} on {datetime:bounce.date}
    {endif}
    </li>
{endeach}
</ul>

<p>
    You may find additionnal details at <a href="{cfg:site_url}">{cfg:site_url}</a>
</p>

<p>
    Best regards,<br />
    {cfg:site_name}
</p>