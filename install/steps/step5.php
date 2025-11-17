<?php
/**
 * Étape 5 : Installation terminée
 */
?>

<div class="step-content final-step">
    <div class="success-icon">🎉</div>
    
    <h2>Installation terminée !</h2>
    <p class="lead">Votre Virtual Airline est maintenant prête à l'emploi.</p>

    <div class="info-box">
        <h3>🔐 Informations de connexion</h3>
        <div class="credentials-box">
            <p><strong>Identifiant :</strong> <code>ADM0001</code></p>
            <p><strong>Mot de passe :</strong> <code>admin123</code></p>
        </div>
        
        <div class="warning-box">
            <strong>⚠️ IMPORTANT - Sécurité :</strong>
            <ol>
                <li>Connectez-vous immédiatement avec le compte ADM0001</li>
                <li>Créez votre propre compte administrateur</li>
                <li><strong>Supprimez le compte ADM0001</strong> (Menu Admin → Gestion Pilotes)</li>
            </ol>
        </div>
    </div>

    <div class="next-steps-box">
        <h3>📋 Prochaines étapes</h3>
        <ol>
            <li>
                <strong>Connexion</strong><br>
                <small>Connectez-vous avec ADM0001 / admin123</small>
            </li>
            <li>
                <strong>Configuration VA</strong><br>
                <small>Personnalisez le nom, logo, et paramètres dans le menu admin</small>
            </li>
            <li>
                <strong>Gestion de la flotte</strong><br>
                <small>Ajoutez vos types d'appareils et votre flotte</small>
            </li>
            <li>
                <strong>Création de missions</strong><br>
                <small>Définissez vos missions et routes</small>
            </li>
            <li>
                <strong>Inscription pilotes</strong><br>
                <small>Ouvrez les inscriptions et accueillez vos pilotes</small>
            </li>
        </ol>
    </div>

    <div class="resources-box">
        <h3>📚 Ressources utiles</h3>
        <ul>
            <li><strong>Documentation :</strong> Consultez le dossier <code>Documentation/</code></li>
            <li><strong>ACARS :</strong> Téléchargez l'addon dans <code>assets/acars/</code></li>
            <li><strong>Support :</strong> Consultez la FAQ et les guides d'utilisation</li>
        </ul>
    </div>

    <div class="security-note">
        <h4>🔒 Note de sécurité</h4>
        <p>L'installateur a été automatiquement verrouillé. Si vous devez réinstaller :</p>
        <ol>
            <li>Supprimez le fichier <code>install/.installed</code></li>
            <li>Supprimez les fichiers <code>includes/db_connect.php</code> et <code>includes/config.php</code></li>
            <li>Rechargez cette page</li>
        </ol>
    </div>

    <div class="actions center">
        <a href="../index.php" class="btn btn-primary btn-large">Accéder à ma Virtual Airline →</a>
    </div>

    <div class="footer-note">
        <p>Merci d'avoir installé Virtual Airline Management System ! ✈️</p>
        <p>Bon vols et bonne gestion de votre compagnie aérienne virtuelle.</p>
    </div>
</div>

<style>
.final-step {
    text-align: center;
}

.success-icon {
    font-size: 80px;
    margin: 20px 0;
}

.lead {
    font-size: 1.2em;
    color: #666;
    margin-bottom: 30px;
}

.credentials-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
    font-size: 1.1em;
}

.credentials-box code {
    background: #e9ecef;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 1.1em;
    color: #d63384;
}

.next-steps-box, .resources-box {
    text-align: left;
    margin: 20px 0;
}

.next-steps-box ol, .resources-box ul {
    line-height: 1.8;
}

.next-steps-box li {
    margin-bottom: 15px;
}

.next-steps-box small {
    color: #666;
    display: block;
    margin-top: 5px;
}

.security-note {
    background: #fff3cd;
    border: 1px solid #ffc107;
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
    text-align: left;
}

.security-note h4 {
    margin-top: 0;
    color: #856404;
}

.security-note ol {
    margin-bottom: 0;
}

.center {
    justify-content: center;
}

.btn-large {
    font-size: 1.2em;
    padding: 15px 30px;
}

.footer-note {
    margin-top: 40px;
    color: #666;
    font-size: 0.95em;
}
</style>
