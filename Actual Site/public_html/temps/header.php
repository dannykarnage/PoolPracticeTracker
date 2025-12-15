        <header>
            <div class="header">
                <div class="left-section">                   
                    <a href="/">
                        <img src="/images/website-logo.png" class ="header-logo" style="border-radius: 2px;"> 
                    </a>
                </div>
                <div class="middle-section">
                    <?php if(empty($_SESSION['username'])): ?>
                        <a href="/">
                            <div class="menu-item">
                                Home
                            </div>
                        </a>
                        <a href="/accounts/login.php">
                            <div class="menu-item">
                                Login
                            </div>
                        </a>
                    <?php else: ?>
                        <a href="/accounts/">
                            <div class="menu-item">
                                My Account
                            </div>
                        </a>
                        <a href="/accounts/logout.php">
                            <div class="menu-item">
                                Logout
                            </div>
                        </a>
                    <?php endif; ?>
                    <a href="/drills">
                        <div class="menu-item">
                            Drills
                        </div>
                    </a>
                    <!--<a href="/contact-us.php">-->
                    <a href="/this-link-doesnt-work.html">
                        <div class="menu-item">
                            Contact Us
                        </div>
                    </a>
                </div>
                <div class="right-section">
                    <!--<a href="/this-link-doesnt-work.html">
                        <img class="patreon-icon" src="/images/icons/patreon_logo.svg">
                    </a>1-->
                    <div></div>
                    <a href="http://www.facebook.com/profile.php?id=61551935931802" target="_blank">
                        <img class="facebook-icon" src="/images/icons/Facebook-f_Logo-Blue-Logo.wine.svg">
                    </a>
                    <a href="https://www.youtube.com/channel/UCvYec5b7AdatNJQpe9P1e4A" target="_blank">
                        <img class="youtube-icon" src="/images/icons/youtube-svgrepo-com.svg">
                    </a>
                </div>    
            </div>
        </header>
