@extends('layouts.master')

@section('title')
Best Uniform | Inline Charts
@endsection

@section('css')
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('asset/fontawesome-free/css/all.min.css')}}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('asset/css/adminlte.min.css')}}">
@endsection

@section('content')

  <div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <!-- Left navbar links -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
          <a href="../../index3.html" class="nav-link">Home</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
          <a href="#" class="nav-link">Contact</a>
        </li>
      </ul>

      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <!-- Navbar Search -->
        <li class="nav-item">
          <a class="nav-link" data-widget="navbar-search" href="#" role="button">
            <i class="fas fa-search"></i>
          </a>
          <div class="navbar-search-block">
            <form class="form-inline">
              <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                  <button class="btn btn-navbar" type="submit">
                    <i class="fas fa-search"></i>
                  </button>
                  <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </li>

        <!-- Messages Dropdown Menu -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="far fa-comments"></i>
            <span class="badge badge-danger navbar-badge">3</span>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <a href="#" class="dropdown-item">
              <!-- Message Start -->
              <div class="media">
                <img src="{{ asset('asset/img/user1-128x128.jpg')}}" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                <div class="media-body">
                  <h3 class="dropdown-item-title">
                    Brad Diesel
                    <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                  </h3>
                  <p class="text-sm">Call me whenever you can...</p>
                  <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                </div>
              </div>
              <!-- Message End -->
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
              <!-- Message Start -->
              <div class="media">
                <img src="{{ asset('asset/img/user8-128x128.jpg')}}" alt="User Avatar" class="img-size-50 img-circle mr-3">
                <div class="media-body">
                  <h3 class="dropdown-item-title">
                    John Pierce
                    <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                  </h3>
                  <p class="text-sm">I got your message bro</p>
                  <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                </div>
              </div>
              <!-- Message End -->
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
              <!-- Message Start -->
              <div class="media">
                <img src="{{ asset('asset/img/user3-128x128.jpg')}}" alt="User Avatar" class="img-size-50 img-circle mr-3">
                <div class="media-body">
                  <h3 class="dropdown-item-title">
                    Nora Silvester
                    <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                  </h3>
                  <p class="text-sm">The subject goes here</p>
                  <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                </div>
              </div>
              <!-- Message End -->
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
          </div>
        </li>
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="far fa-bell"></i>
            <span class="badge badge-warning navbar-badge">15</span>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-item dropdown-header">15 Notifications</span>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
              <i class="fas fa-envelope mr-2"></i> 4 new messages
              <span class="float-right text-muted text-sm">3 mins</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
              <i class="fas fa-users mr-2"></i> 8 friend requests
              <span class="float-right text-muted text-sm">12 hours</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
              <i class="fas fa-file mr-2"></i> 3 new reports
              <span class="float-right text-muted text-sm">2 days</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
          </div>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-widget="fullscreen" href="#" role="button">
            <i class="fas fa-expand-arrows-alt"></i>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
            <i class="fas fa-th-large"></i>
          </a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>Inline Charts</h1>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Inline Charts</li>
              </ol>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <!-- row -->
          <div class="row">
            <div class="col-12">
              <!-- jQuery Knob -->
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">
                    <i class="far fa-chart-bar"></i>
                    jQuery Knob
                  </h3>

                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                      <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                  <div class="row">
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="30" data-width="90" data-height="90" data-fgColor="#3c8dbc">

                      <div class="knob-label">New Visitors</div>
                    </div>
                    <!-- ./col -->
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="70" data-width="90" data-height="90" data-fgColor="#f56954">

                      <div class="knob-label">Bounce Rate</div>
                    </div>
                    <!-- ./col -->
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="-80" data-min="-150" data-max="150" data-width="90"
                            data-height="90" data-fgColor="#00a65a">

                      <div class="knob-label">Server Load</div>
                    </div>
                    <!-- ./col -->
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="40" data-width="90" data-height="90" data-fgColor="#00c0ef">

                      <div class="knob-label">Disk Space</div>
                    </div>
                    <!-- ./col -->
                  </div>
                  <!-- /.row -->

                  <div class="row">
                    <div class="col-6 text-center">
                      <input type="text" class="knob" value="90" data-width="90" data-height="90" data-fgColor="#932ab6">

                      <div class="knob-label">Bandwidth</div>
                    </div>
                    <!-- ./col -->
                    <div class="col-6 text-center">
                      <input type="text" class="knob" value="50" data-width="90" data-height="90" data-fgColor="#39CCCC">

                      <div class="knob-label">CPU</div>
                    </div>
                    <!-- ./col -->
                  </div>
                  <!-- /.row -->
                </div>
                <!-- /.card-body -->
              </div>
              <!-- /.card -->
            </div>
            <!-- /.col -->
          </div>
          <!-- /.row -->

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">
                    <i class="far fa-chart-bar"></i>
                    jQuery Knob Different Sizes
                  </h3>

                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                      <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                  <div class="row">
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="30" data-width="90" data-height="90" data-fgColor="#3c8dbc"
                            data-readonly="true">

                      <div class="knob-label">data-width="90"</div>
                    </div>
                    <!-- ./col -->
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="30" data-width="120" data-height="120"
                            data-fgColor="#f56954">

                      <div class="knob-label">data-width="120"</div>
                    </div>
                    <!-- ./col -->
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="30" data-thickness="0.1" data-width="90" data-height="90"
                            data-fgColor="#00a65a">

                      <div class="knob-label">data-thickness="0.1"</div>
                    </div>
                    <!-- ./col -->
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" data-thickness="0.2" data-angleArc="250" data-angleOffset="-125"
                            value="30" data-width="120" data-height="120" data-fgColor="#00c0ef">

                      <div class="knob-label">data-angleArc="250"</div>
                    </div>
                    <!-- ./col -->
                  </div>
                  <!-- /.row -->
                </div>
                <!-- /.card-body -->
              </div>
              <!-- /.card -->
            </div>
            <!-- /.col -->
          </div>
          <!-- /.row -->

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">
                    <i class="far fa-chart-bar"></i>
                    jQuery Knob Tron Style
                  </h3>

                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                      <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                  <div class="row">
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="80" data-skin="tron" data-thickness="0.2" data-width="90"
                            data-height="90" data-fgColor="#3c8dbc" data-readonly="true">

                      <div class="knob-label">data-width="90"</div>
                    </div>
                    <!-- ./col -->
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="60" data-skin="tron" data-thickness="0.2" data-width="120"
                            data-height="120" data-fgColor="#f56954">

                      <div class="knob-label">data-width="120"</div>
                    </div>
                    <!-- ./col -->
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="10" data-skin="tron" data-thickness="0.1" data-width="90"
                            data-height="90" data-fgColor="#00a65a">

                      <div class="knob-label">data-thickness="0.1"</div>
                    </div>
                    <!-- ./col -->
                    <div class="col-6 col-md-3 text-center">
                      <input type="text" class="knob" value="100" data-skin="tron" data-thickness="0.2"
                            data-angleArc="250" data-angleOffset="-125" data-width="120" data-height="120"
                            data-fgColor="#00c0ef">

                      <div class="knob-label">data-angleArc="250"</div>
                    </div>
                    <!-- ./col -->
                  </div>
                  <!-- /.row -->
                </div>
                <!-- /.card-body -->
              </div>
              <!-- /.card -->
            </div>
            <!-- /.col -->
          </div>
          <!-- /.row -->

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">
                    <i class="far fa-chart-bar"></i>
                    Sparklines
                  </h3>

                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                      <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                  <div class="row">
                    <div class="col-4 text-center">
                      <div id="sparkline-1"></div>
                    </div>
                    <!-- ./col -->
                    <div class="col-4 text-center">
                      <div id="sparkline-2"></div>
                    </div>
                    <!-- ./col -->
                    <div class="col-4 text-center">
                      <div id="sparkline-3"></div>
                    </div>
                    <!-- ./col -->
                  </div>
                  <!-- /.row -->
                </div>
                <!-- /.card-body -->
              </div>
              <!-- /.card -->
            </div>
            <!-- /.col -->
          </div>
          <!-- /.row -->

        </div><!-- /.container-fluid -->
      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- /.control-sidebar -->
  </div>
<!-- ./wrapper -->
  @section('script')
    <!-- jQuery Knob -->
    <script src=".{{ asset('asset/jquery-knob/jquery.knob.min.js')}}"></script>
    <!-- Sparkline -->
    <script src="{{ asset('asset/sparklines/sparkline.js')}}"></script>
    <!-- Page specific script -->
    <script>
      $(function () {
        /* jQueryKnob */

        $('.knob').knob({
          /*change : function (value) {
          //console.log("change : " + value);
          },
          release : function (value) {
          console.log("release : " + value);
          },
          cancel : function () {
          console.log("cancel : " + this.value);
          },*/
          draw: function () {

            // "tron" case
            if (this.$.data('skin') == 'tron') {

              var a   = this.angle(this.cv)  // Angle
                ,
                  sa  = this.startAngle          // Previous start angle
                ,
                  sat = this.startAngle         // Start angle
                ,
                  ea                            // Previous end angle
                ,
                  eat = sat + a                 // End angle
                ,
                  r   = true

              this.g.lineWidth = this.lineWidth

              this.o.cursor
              && (sat = eat - 0.3)
              && (eat = eat + 0.3)

              if (this.o.displayPrevious) {
                ea = this.startAngle + this.angle(this.value)
                this.o.cursor
                && (sa = ea - 0.3)
                && (ea = ea + 0.3)
                this.g.beginPath()
                this.g.strokeStyle = this.previousColor
                this.g.arc(this.xy, this.xy, this.radius - this.lineWidth, sa, ea, false)
                this.g.stroke()
              }

              this.g.beginPath()
              this.g.strokeStyle = r ? this.o.fgColor : this.fgColor
              this.g.arc(this.xy, this.xy, this.radius - this.lineWidth, sat, eat, false)
              this.g.stroke()

              this.g.lineWidth = 2
              this.g.beginPath()
              this.g.strokeStyle = this.o.fgColor
              this.g.arc(this.xy, this.xy, this.radius - this.lineWidth + 1 + this.lineWidth * 2 / 3, 0, 2 * Math.PI, false)
              this.g.stroke()

              return false
            }
          }
        })
        /* END JQUERY KNOB */

        //INITIALIZE SPARKLINE CHARTS
        var sparkline1 = new Sparkline($('#sparkline-1')[0], { width: 240, height: 70, lineColor: '#92c1dc', endColor: '#92c1dc' })
        var sparkline2 = new Sparkline($('#sparkline-2')[0], { width: 240, height: 70, lineColor: '#f56954', endColor: '#f56954' })
        var sparkline3 = new Sparkline($('#sparkline-3')[0], { width: 240, height: 70, lineColor: '#3af221', endColor: '#3af221' })

        sparkline1.draw([1000, 1200, 920, 927, 931, 1027, 819, 930, 1021])
        sparkline2.draw([515, 519, 520, 522, 652, 810, 370, 627, 319, 630, 921])
        sparkline3.draw([15, 19, 20, 22, 33, 27, 31, 27, 19, 30, 21])

      })

    </script>
  @endsection
@endsection
