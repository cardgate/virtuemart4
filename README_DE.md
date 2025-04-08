![CardGate](https://cdn.curopayments.net/thumb/200/logos/cardgate.png)

# CardGate Modul für VirtueMart 4.x

## Support

Dieses Modul is geeignet für VirtueMart version **4.x** mit den Joomla Versionen **5.x**

## Vorbereitung

Um dieses Modul zu verwenden, sind Zugangsdaten zu **CardGate** notwendig.  
Gehen Sie zu [**Mein CardGate**](https://my.cardgate.com/) und fragen Sie Ihre **Site ID** und **Hash Key** an, oder kontaktieren Sie Ihren Accountmanager.

## Installation

1. Downloaden Sie den aktuellsten [**cardgate.zip**](https://github.com/cardgate/virtuemart4/releases) Datei auf Ihrem Desktop.

2. Gehen Sie zum **Adminbereich** Ihres Webshops und wählen Sie **Extensionmanager** aus **Extensions** aus.
 
3. Klicken Sie bei der Option **Datei hochladen** auf **Browse...**  
   Selektieren Sie den **cardgate.zip** Ordner.
   
4. Klicken Sie auf den **Datei hochladen & installlieren** Button.
  
## Configuration

1. Gehen Sie zum **Admin**-Bereich Ihres Webshops.

2. Selektieren Sie nun **Components, Virtuemart, Payment Methods**.

3. Klicken Sie auf den Button **Neu**. 

4. Wählen Sie das **Payment Method Information** Tab aus.

5. Füllen Sie den **Name** der Zahlungsmethode aus und wählen Sie die gewünschte **Zahlungsmethode**.

6. Füllen Sie weitere Informationen in diesem Tab ein und klicken Sie auf Speichern.

7. Wählen Sie nun den **Konfigurations** Tab und klicken Sie auf speichern.

8. Fügen Sie die **Site ID** und den **Hash Key** ein, Diesen können Sie unter Webseiten bei [**Mein CardGate**](https://my.cardgate.com/) finden.

9. Fügen Sie die **Merchant ID** und den **API Key** ein, die Sie von Ihrem Accountmanager erhalten.

10. Fügen Sie weitere relevante Konfigurationsinformation ein und klicken Sie auf **Speichern** und **Schließen**.

11. Wiederholen Sie die Schritte 3 bis 10 für jede **Zahlungsmethode**, die Sie aktivieren möchten.

12. Gehen Sie zu [**Mein CardGate**](https://my.cardgate.com/), und wählen die gewünschten **Seiten** aus.

13. Füllen Sie nun bei **Technische Schnittstelle** die **Callback URL** ein, z.B.   
    **http://meinwebshop.com/index.php?option=com_cgp&task=callback**
    (Ersetzen Sie **http://meinwebshop.com** mit der URL Ihres Webshops.) 

14. Sorgen Sie dafür, dass Sie nach dem Testen **alle aktivierten Zahlungsmethoden** vom **Testmode** in **Livemode** und **Speichern** Sie die Einstellung. 
 
## Anforderungen

Keine weiteren Anforderungen.

