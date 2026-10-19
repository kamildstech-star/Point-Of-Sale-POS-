<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{asset('asset/fontawesome-free/css/all.min.css')}}">
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Didact+Gothic&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.css" integrity="sha512-6lLUdeQ5uheMFbWm3CP271l14RsX1xtx+J5x2yeIDkkiBpeVTNhTqijME7GgRKKi6hCqovwCoBTlRBEC20M8Mg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.css" integrity="sha512-wR4oNhLBHf7smjy0K4oqzdWumd+r5/+6QO/vDda76MW5iug4PT7v86FoEkySIJft3XA0Ae6axhIvHrqwm793Nw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Antonio:wght@100..700&family=Didact+Gothic&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Antonio:wght@100..700&family=Didact+Gothic&display=swap" rel="stylesheet">
    <link href="https://db.onlinewebfonts.com/c/c6ec8e4abd0865396251cbee93465313?family=ChampionGothic-Bantamweight" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <title>Frontend</title>
  <style>

* {
  scroll-behavior: smooth;
}
.rich{
  padding-bottom: 60px;
}
.rich h1{
    position: relative;
    top: -110px;
    margin-top: 120px;
    margin-bottom: -110px;
}
.social-icon-wrapper ul{
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    list-style-type: none;
    display: block;
    margin-block-start: 1em;
    margin-block-end: 1em;
}

.richtext-container{
  padding: 60px 0px;
}
.footer-content{
  padding: 0 5rem;
  margin: 0 auto;
}

.footer-block-newsletter{
  flex-wrap: wrap;
  flex-direction: column;
  align-items: center;
  margin: 3rem 0px;
  text-align: center;

}
.footer-block-newsletter h2{
  font-weight: 700;
   font-family: "Montserrat", serif;
   color: rgb(0, 65, 107);

}
.footer-block-newsletter h3{
  font-weight: 700;
   font-family: "Montserrat", serif;
   color: rgb(0, 65, 107);
   font-size: 1rem;
   margin: 0 0 15px;


}
.form form input{
  border-left: 0px;
    border-right: 0px;
    border-top: 0px;
    border-bottom: 1px solid black;

    font-size: 30px;
    text-align: center;
    background-color: transparent;
    flex-direction: column;
}
.form form button{
  margin-top: 1.2rem;
    padding: 10px 50px;
    border-radius: 30px;
    background: #ffcb05 !important;
    border: none;
    font-weight: 900;
    font-size: 24px;
    font-family: Montserrat ", serif";
    color: #00416b !important;
}
.form form .form-wrapper{
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}
.form form{
  display: flex;
  justify-content: center;
  align-items: center;
}
.footer-block-menu{
    width: 100% !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    max-width: 76rem;
    margin: 0 auto;
}
.footer-block-menu .footer-block-heading{
   font-weight: 700;
   font-family: "Montserrat", serif;
   color: rgb(0, 65, 107);


}
.footer-block-content{
  display:flex;
  align-items: center;
  list-style: none;
  margin: 0;
  padding: 0;
}
.footer-block-content li{
 margin-right:1.5rem;
}

.custom-services-wrapper{
  text-align: center;
}
.custom-services-wrapper .custom-service-heading{
    font-size: 40px;
    margin-top: 50px;
    font-family: "Montserrat", serif;
    font-style: normal;
    font-weight: bold;
    letter-spacing: -1;
    color: rgb(0, 65, 107);
  }
.custom-services-wrapper .number-heading{
    font-size: 200px;
    font-family: "ChampionGothic-Bantamweight";
    font-weight: 300;
    letter-spacing: normal;
    margin: 0;
    color: rgb(0, 65, 107);
  }
  .footer{
    background-color: #f6f6f6;
    padding-top: 68px;
    padding-bottom: 12px;
  }
  #shopify-section-sections--16988182020256__footer footer {
}
.section-sections--16988182020256__footer-padding {
}
  
.rich h1{
  font-family: "ChampionGothic-Bantamweight";
    font-size: 150px;
    margin-bottom: 30px;
    line-height: 1.19;
    letter-spacing: normal;
    text-align: center;
    color: #fff;
    margin-top: 0;
    -webkit-text-stroke-width: 1px;
    -webkit-text-stroke-color: #00416B;
}

.richtext-slider .slick-dots{
  text-align: left;
  margin-left: 2%;
}
.slick-dots li button{
  display: block;
    width: 1.2rem;
    height: 1.2rem;
    padding: 0;
    border: 1px solid #0096c7;
    border-radius: 100%;
    background-color: white;
    text-indent: -9999px;
}
.rich .container p {
    font-size: 18px;
    font-weight: 400;
    font-stretch: normal;
    font-style: normal;
    line-height: 1.44;
    letter-spacing: normal;
    text-align: center;
    color: #fff;
    max-width: 800px;
    margin: 50px auto 0;
}
.rich .container{
   padding:0px 0px;
}
/* .slick-dots{
  text-align: left;
  margin-left: 2%;
} */
.slick-dots .slick-active button{
  display: block;
    width: 1.2rem;
    height: 1.2rem;
    padding: 0;
    border: 2px solid black;
    padding: 1px;
    border-radius: 100%;
    background-color: #0096c7;
    text-indent: -9999px;
}

    #menuToggle
{
  display: block;
  position: fixed;
  top: 50px;
  left: 50px;
  z-index: 1;
  -webkit-user-select: none;
  user-select: none;
}
/* .icon-section{
  margin:0px 10px;
} */

#menuToggle a
{
  text-decoration: none;
  color: #232323;
  
  transition: color 0.3s ease;
}
#menuToggle a:hover
{
  color: tomato;
}

.image img{
  height: 70px;
  
}
body{
  background-color: #f8f9fa;

}

#menuToggle input
{
  display: block;
  width: 40px;
  height: 32px;
  position: absolute;
  top: -7px;
  left: -5px;
  
  cursor: pointer;
  
  opacity: 0; /* hide this */
  z-index: 2; /* and place it over the hamburger */
  
  -webkit-touch-callout: none;
}

#menuToggle span
{
  display: block;
  width: 33px;
  height: 4px;
  margin-bottom: 5px;
  position: relative;
  
  background: #cdcdcd;
  border-radius: 3px;
  
  z-index: 1;
  
  transform-origin: 4px 0px;
  
  transition: transform 0.5s cubic-bezier(0.77,0.2,0.05,1.0),
              background 0.5s cubic-bezier(0.77,0.2,0.05,1.0),
              opacity 0.55s ease;
}

#menuToggle span:first-child
{
  transform-origin: 0% 0%;
}

#menuToggle span:nth-last-child(2)
{
  transform-origin: 0% 100%;
}

/* 
 * Transform all the slices of hamburger
 * into a crossmark.
 */
#menuToggle input:checked ~ span
{
  opacity: 1;
  transform: rotate(45deg) translate(-2px, -1px);
  background: #232323;
}

/*
 * But let's hide the middle one.
 */
#menuToggle input:checked ~ span:nth-last-child(3)
{
  opacity: 0;
  transform: rotate(0deg) scale(0.2, 0.2);
}

/*
 * Ohyeah and the last one should go the other direction
 */
#menuToggle input:checked ~ span:nth-last-child(2)
{
  transform: rotate(-45deg) translate(0, -1px);
}
#search-bar{
  border-left:none;
  border-right:none;
  border-top:none;
  border-radius:0px;
}
.header-wrap{
  position: relative;
  margin-top: 50px;
}
.header-wrap h1{
  font-family: "ChampionGothic-Bantamweight";
  font-weight: 400;
  font-style: normal;
  /* font-family: "Didact Gothic", serif; */
    font-size: 155px;
    font-weight: 300;
    /*font-style: normal; */
    line-height: 1.19;
    text-align: center;
    color: #ffcb05;
    margin: 0;
}
.header-wrap p {
    text-align: center;
    font-size: 17px;
    margin-top: -48px;
    color: #00416B;
}
@media (min-width:750px) {
  .header-wrap p {
    margin-top: -35px;
    font-size: 30px;
  }
}
.gx-unfrmrs-unfrm-cllctn{
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: stretch;
}
.gx-unfrmrs-unfrm-cllctn a {
    margin: 0 10px 20px;
    background: #fff;
    width: 23.2%;
    padding: 70px;
}
.gx-unfrmrs-img-fluid {
    display: block;
    margin: auto;
    max-width: 100%;
    height: auto;
}
.richtext-wraper{
  background-color: #ffcb05;
  border-radius: 10px;

}
/*
 * Make this absolute positioned
 * at the top left of the screen
 */
.gx-unfrmrs-container {
    position: relative;
    top: -110px;
    margin-top: 150px;
    margin-bottom: -110px;
}
.richtext-quotes .slide-inner h3 {
    color: #ffcb05;
    font-weight: 500;
    font-size: 160px;
    font-family: "ChampionGothic-Bantamweight";
    margin: 0;
}
#menu
{
  position: absolute;
  max-width: 400px;
  width: 100vw;
  max-height: 100vh;
  margin: -100px 0 0 -50px;
  padding: 50px;
  padding-top: 125px;
  box-sizing: border-box;
  overflow-y: auto;
  background: #ededed;
  list-style-type: none;
  -webkit-font-smoothing: antialiased;
  /* to stop flickering of text in safari */
  
  transform-origin: 0% 0%;
  transform: translate(-100%, 0);
  
  transition: transform 0.5s cubic-bezier(0.77,0.2,0.05,1.0);
}
.richtext-wraper {
    background-color: #ffcb05;
    border-radius: 88px;
    color: midnightblue;
    padding: 29px 32px;
    width: 85%;
}
.richtext-wraper h1{
        margin-top: -80px;
        font-size: 84px;
        color: #00416b;
        font-family: "ChampionGothic-Bantamweight";
        font-weight: 300;
}
.richtext-slider{
  margin-top:-28px;
}
.richtext-quotes .slick-track .slide-inner{
  text-align: center;
  
}
.we-are .slick-track .slick-slide{
  display: flex;
  height: auto;
  align-items: center;
  justify-content: center;
}

#menu li
{
  padding: 10px 0;
  font-size: 22px;
}

#menu li label
{
  cursor: pointer;
}
.slide-inner {
    padding: 0 30px;
}
.slide-inner h3 {
    font-weight: 700;
    font-size: 25px;
    color: #00416b;
    margin: 20px 0 0;
}
.bottom-content {
    color: #00416b;
}

/*
 * And let's slide it in from the left
 */
#menuToggle input:checked ~ ul
{
  transform: none;
}

  </style>
</head>
<body>
  <div class="container-fluid bg-body-white bg-white">
     <nav class="navbar navbar-expand-lg bg-body-white bg-white" >
      <div class="container-fluid bg-body-white">
        <a class="navbar-brand" href="#">Navbar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="col-8">
            <div class="image text-center">
                <img src="{{ asset('asset/img/logo.png')}}" alt="" srcset="">
            </div>
        </div>
              <form class=" d-flex" role="search">
                  <button class="btn" type="submit"><i class="fa fa-search " style="font-size:20px"></i></button>
                  <input class="form-control me-2" id="search-bar" type="search" placeholder="Search" aria-label="Search">
              </form>
              <i class="fa fa-shopping-bag " style="font-size:20px"></i>
              <i class="fa fa-user" style="font-size:20px"></i>
        
        
        <!-- <div class="collapse navbar-collapse me-auto" id="navbarNavAltMarkup">
          <div class="navbar-nav">
            <a class="nav-link active" aria-current="page" href="#">Home</a>
            <a class="nav-link" href="#">Features</a>
            <a class="nav-link" href="#">Pricing</a>
            <a class="nav-link disabled" aria-disabled="true">Disabled</a>
          </div>
        </div> -->
      </div>
    </nav>
    <!-- <div class="row bg-primary" style="background-color:black;height:20px;">
        <nav role="navigation" >
            <div id="menuToggle">
                <input type="checkbox" id="menuCheckbox" />
                <span></span>
                <span></span>
                <span></span>
                <ul id="menu">
                    <li>
                      <a href="#">
                        <label for="menuCheckbox" onclick="this.parentNode.click();">Home</label>
                      </a>
                    </li>
                    <li>
                      <a href="#about">
                        <label for="menuCheckbox" onclick="this.parentNode.click();">About</label>
                      </a>
                    </li>
                    <li><label for="menuCheckbox"><a>Info</a></label></li>
                    <li><label for="menuCheckbox"><a>Contact</a></label></li>
                    <li>
                      <a href="https://erikterwan.com/" target="_blank">Show me more</a>
                    </li>
                </ul>
            </div>
        </nav>
    </div> -->
  </div>
  <div class="single-item" width="">
    <div ><img src="{{ asset('asset/img/1.webp') }}" alt="" style="max-width:100%"></div>
    <div ><img src="{{ asset('asset/img/2.webp') }}" alt="" style="max-width:100%"></div>
    <div ><img src="{{ asset('asset/img/3.webp') }}" alt="" style="max-width:100%"></div>
    <div ><img src="{{ asset('asset/img/4.webp') }}" alt="" style="max-width:100%"></div>
  </div>
  
<div class="gx-unfrmrs-container">
  <div class="header-wrap">
    <h1>what’s your school?</h1>
    <p>choose from below to buy uniform</p>
  </div>
  <div class="gx-unfrmrs-unfrm-cllctn">
                           
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=Kips">
                    <img src="{{ asset('asset/img/schools/educators.avif') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=MGS">
                    <img src="{{ asset('asset/img/schools/kips.avif') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=the_educators">
                    <img src="{{ asset('asset/img/schools/LACAS.avif') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=Lecole">
                    <img src="{{ asset('asset/img/schools/lecole.avif') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=Scholastic_Islamia">
                    <img src="{{ asset('asset/img/schools/message-grammer.avif') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=Scil">
                    <img src="{{ asset('asset/img/schools/pgc.webp') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=Lacas">
                    <img src="{{ asset('asset/img/schools/scholastic.avif ') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=Lacas_Milestone">
                    <img src="{{ asset('asset/img/schools/scil.avif') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=Bricks">
                    <img src="{{ asset('asset/img/schools/Concordia.avif') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=Bricks">
                    <img src="{{ asset('asset/img/schools/pf-74cab063--AES.avif') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=Bricks">
                    <img src="{{ asset('asset/img/schools/Alma.avif') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                  <a href="https://uniformers.pk/pages/uniform-selection-page?vendor=Bricks">
                    <img src="{{ asset('asset/img/schools/images.avif') }}" alt="" srcset="" class="gx-unfrmrs-img-fluid">
                  </a>
                 
              </div>
              </div>
                <div class="richtext-container" style="background-color:white">
                  <div class="container">
                    <div class="row">
                        <div class="richtext-wraper mt-5">
                            <h1 class="heading">why Uniformers</h1>
                            <div class="content"><p>For the first time UNIFORMERS “<strong>The School Shop” introduces the most advanced size grading system, All you have to do is just to convey your child’s height and get complete uniform.</strong></p></div>
                            
                        </div>
                        <div class="single-item richtext-slider" width="">
                                <div class="slide-inner" >
                                  <img src="{{ asset('asset/img/buy-faults.avif') }}" alt="" style="max-width:100px">
                                    <h3>We Buy Our Faults</h3>
                                    <div class="bottom-content"><p>100% Money Back Guarantee against any manufacturing fault.</p></div>
                                </div>
                                <div class="slide-inner">
                                  <img src="{{ asset('asset/img/aletr-free-garments-icon.avif') }}" alt="" style="max-width:100px">
                                <h3>Alter Free Garment</h3>
                                  <div class="bottom-content"><p>No alteration required, however minor adjustments will be made free of cost.</p></div>
                                </div>

                                <div class="slide-inner">
                                  <img src="{{ asset('asset/img/copoative-icon.avif') }}" alt="" style="max-width:100px">
                                <h3>Cooperative Staff</h3>
                                  <div class="bottom-content"><p>Customer friendly staff at your service anytime.</p></div>
                                </div>
                    </div>
                  </div>
              </div>
              </div>
              <div class="rich" style="background-color:#00416B">
                <h1>we are</h1>
                <div class="container">
                  <div class="row">
                  <div class="we-are" width="">
                                <div class="slide-inner"  >
                                  <img src="{{ asset('asset/img/team.webp') }}" alt="" style="max-width:100px">
                                </div>
                                <div class="slide-inner">
                                  <img src="{{ asset('asset/img/handshake_3.webp') }}" alt="" style="max-width:100px">
                                </div>
                                <div class="slide-inner">
                                  <img src="{{ asset('asset/img/trophy.webp') }}" alt="" style="max-width:100px">
                                </div>
                                <div class="slide-inner">
                                  <img src="{{ asset('asset/img/light-bulb_1.webp') }}" alt="" style="max-width:100px">
                                </div>
                                <div class="slide-inner">
                                  <img src="{{ asset('asset/img/puzzle.webp') }}" alt="" style="max-width:100px">
                                </div>
                                <div class="slide-inner">
                                  <img src="{{ asset('asset/img/healthcare.webp') }}" alt="" style="max-width:100px">
                                </div>
                    </div>
                    <div class="row">
                    <div class="richtext-quotes" width="">
                               
                                <div class="slide-inner">
                                <h3>Committed</h3>
                                </div>
                                <div class="slide-inner">
                                <h3>Excellent</h3>
                                </div>
                                <div class="slide-inner">
                                   <h3>Innovative</h3>
                                </div>
                                <div class="slide-inner">
                                    <h3>Integral</h3>
                                </div>
                                <div class="slide-inner">
                                    <h3>Careful</h3>
                                </div>
                                <div class="slide-inner" >
                                    <h3>Collaborative</h3>
                                </div>
                    </div>
                    </div>
                    <p>Innovating professional and school-wear, UNIFORMERS has become a trusted name in 21 years, serving prestigious institutions across Pakistan</p>

                  </div>
                </div>
              </div>
              <div class="custom-services-wrapper">
              <h2 class="custom-service-heading">
                           Unmatched Customer Services
              </h2>
              <h2 class="number-heading">
                      03 111 666 123
              </h2>
              </div>
              <div class="footer">
                <div class="footer-content">
                  <div class="footer-block-menu">
                  <h2 class="footer-block-heading">Quick links</h2>
                  <ul class="footer-block-content" style="list-style:none;">
                        <li>
                          <a href="/pages/store-locator" class="link link--text list-menu__item list-menu__item--link">
                            Store Locator
                          </a>
                        </li>
                        <li>
                          <a href="/pages/contact-us" class="link link--text list-menu__item list-menu__item--link">
                            Contact Us
                          </a>
                        </li>
                        <li>
                          <a href="/policies/refund-policy" class="link link--text list-menu__item list-menu__item--link">
                            Refund Policy
                          </a>
                        </li>
                        <li>
                          <a href="/policies/terms-of-service" class="link link--text list-menu__item list-menu__item--link">
                            Terms of services
                          </a>
                        </li>
                    </ul>
                  </div>
                </div>
                <div class="footer-block-newsletter">
                    <div class="footer-block-newsletter">
                      <h2>GET IN TOUCH</h2>
                      <div class="newletter-content">
                        <h3>
                            for the practical parenting tips
                        </h3>
                      </div>
                    </div>
                    <div class="form">
                      <form action="">
                        <div class="form-wrapper">
                          <input type="text" name="" id="" class="" placeholder="your email address">
                          <button type="submit" class="">Connect</button>
                        </div>
                      </form>
                    </div>
                </div>
                <div class="social-icon-wrapper">
                  <ul>
                    <li class="social-links">
                      <a href="">
                        <img src="{{ asset('asset/img/twitter.png') }}" >
                      </a>
                    </li>
                    <li class="social-links">
                      <a href="">
                        <img src="{{ asset('asset/img/instagram.png') }}" >
                      </a>
                    </li>
                    <li class="social-links">
                      <a href="">
                        <img src="{{ asset('asset/img/youtube.png') }}" >
                      </a>
                    </li>
                    <li class="social-links">
                      <a href="">
                         <img src="{{ asset('asset/img/linkedin-logo.png') }}" >
                     </a>

                    </li>
                    <li class="social-links">
                      <a href="">
                        <img src="{{ asset('asset/img/facebook.png') }}" >
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
              
  <!-- <div  class="single-item">
  <div><h3>1</h3></div>
  <div><h3>2</h3></div>
  <div><h3>3</h3></div>
  <div><h3>4</h3></div>
  <div><h3>5</h3></div>
  <div><h3>6</h3></div>
</div> -->
<script src="{{ asset('asset/jquery/jquery.ajax.libs.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js" integrity="sha512-HGOnQO9+SP1V92SrtZfjqxxtLmVzqZpjFFekvzZVWoiASSQgSr4cw9Kqd2+l8Llp4Gm0G8GIFJ4ddwZilcdb8A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
      $('.single-item').slick({
        dots: true,
        arrows: false,
        autoplay: true,
        infinite: true,
        speed: 300,
        slidesToShow: 1,
      });
      $('.we-are').slick({
        arrows: false,
        // infinite: true,
        slidesToShow: 3,
      });
      $('.richtext-quotes').slick({
        arrows: false,
        asNavFor: $('.richtext-quotes, .we-are')
      });

    </script>
</body>
</html>


  


