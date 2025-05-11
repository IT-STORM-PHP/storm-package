<?php
    namespace StormBin\Package\MiddleWare;
    use StormBin\Package\Auth\Auth;
    class AuthMiddleware
    {
        public function handle()
        {
            /*if (!isset($_SESSION['user_id'])) {
                http_response_code(403);
                echo "⚠️ Accès refusé. Veuillez vous connecter.";
                exit();
            }*/
            Auth::refreshSession();
        }
    }
    
?>
