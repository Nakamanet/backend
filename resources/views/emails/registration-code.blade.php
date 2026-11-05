<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ton code NAKAMANET</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1a1a24;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;">
                    <tr>
                        <td align="center" style="padding:0 0 24px;">
                            {{-- Logo joint au mail (CID) : il s'affiche sans dépendre du site --}}
                            <img src="{{ $message->embed(resource_path('images/email-logo.png')) }}" width="240" alt="NAKAMANET" style="display:block;width:240px;max-width:100%;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <h1 style="margin:0 0 16px;font-size:22px;color:#1a1a24;">Bienvenue sur NAKAMANET !</h1>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.5;color:#3a3a48;">
                                Voici ton code pour finaliser ton inscription :
                            </p>
                            <p style="margin:0 0 24px;padding:16px;background:#f4f4f6;border-radius:12px;font-size:34px;font-weight:bold;letter-spacing:8px;text-align:center;color:#1a1a24;">
                                {{ $code }}
                            </p>
                            <p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#6b6b78;">
                                Ce code expire dans {{ $ttlMinutes }} minutes.
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.5;color:#6b6b78;">
                                Si tu n'as pas demandé à créer un compte, ignore simplement cet email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
