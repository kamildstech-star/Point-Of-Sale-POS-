@extends('layouts.master')

@section('title')
Best Uniform | UI Ribbons
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
              <h1>Ribbons</h1>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Ribbons</li>
              </ol>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <div class="row">
            <div class="col-12">
              <div class="card card-primary">
                <div class="card-header">
                  <h3 class="card-title">Ribbons</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                  <div class="row">
                    <div class="col-sm-4">
                      <div class="position-relative p-3 bg-gray" style="height: 180px">
                        <div class="ribbon-wrapper">
                          <div class="ribbon bg-primary">
                            Ribbon
                          </div>
                        </div>
                        Ribbon Default <br />
                        <small>.ribbon-wrapper.ribbon-lg .ribbon</small>
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="position-relative p-3 bg-gray" style="height: 180px">
                        <div class="ribbon-wrapper ribbon-lg">
                          <div class="ribbon bg-info">
                            Ribbon Large
                          </div>
                        </div>
                        Ribbon Large <br />
                        <small>.ribbon-wrapper.ribbon-lg .ribbon</small>
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="position-relative p-3 bg-gray" style="height: 180px">
                        <div class="ribbon-wrapper ribbon-xl">
                          <div class="ribbon bg-secondary">
                            Ribbon Extra Large
                          </div>
                        </div>
                        Ribbon Extra Large <br />
                        <small>.ribbon-wrapper.ribbon-xl .ribbon</small>
                      </div>
                    </div>
                  </div>
                  <div class="row mt-4">
                    <div class="col-sm-4">
                      <div class="position-relative p-3 bg-gray" style="height: 180px">
                        <div class="ribbon-wrapper ribbon-lg">
                          <div class="ribbon bg-success text-lg">
                            Ribbon
                          </div>
                        </div>
                        Ribbon Large <br /> with Large Text <br />
                        <small>.ribbon-wrapper.ribbon-lg .ribbon.text-lg</small>
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="position-relative p-3 bg-gray" style="height: 180px">
                        <div class="ribbon-wrapper ribbon-xl">
                          <div class="ribbon bg-warning text-lg">
                            Ribbon
                          </div>
                        </div>
                        Ribbon Extra Large <br /> with Large Text <br />
                        <small>.ribbon-wrapper.ribbon-xl .ribbon.text-lg</small>
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="position-relative p-3 bg-gray" style="height: 180px">
                        <div class="ribbon-wrapper ribbon-xl">
                          <div class="ribbon bg-danger text-xl">
                            Ribbon
                          </div>
                        </div>
                        Ribbon Extra Large <br /> with Extra Large Text <br />
                        <small>.ribbon-wrapper.ribbon-xl .ribbon.text-xl</small>
                      </div>
                    </div>
                  </div>
                  <div class="row mt-4">
                    <div class="col-sm-4">
                      <div class="position-relative">
                        <img src="{{ asset('asset/img/photo1.png')}}" alt="Photo 1" class="img-fluid">
                        <div class="ribbon-wrapper ribbon-lg">
                          <div class="ribbon bg-success text-lg">
                            Ribbon
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="position-relative">
                        <img src="{{ asset('asset/img/photo2.png')}}" alt="Photo 2" class="img-fluid">
                        <div class="ribbon-wrapper ribbon-xl">
                          <div class="ribbon bg-warning text-lg">
                            Ribbon
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="position-relative" style="min-height: 180px;">
                        <img src="{{ asset('asset/img/photo3.jpg')}}" alt="Photo 3" class="img-fluid">
                        <div class="ribbon-wrapper ribbon-xl">
                          <div class="ribbon bg-danger text-xl">
                            Ribbon
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- /.card-body -->
              </div>
              <!-- /.card -->
            </div>
            <!-- /.col -->
          </div>
          <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

  </div>
<!-- ./wrapper -->
  @section('script')
    <!-- Page specific script -->
    <script>
      $(function () {
        /* BOOTSTRAP SLIDER */
        $('.slider').bootstrapSlider()

        /* ION SLIDER */
        $('#range_1').ionRangeSlider({
          min     : 0,
          max     : 5000,
          from    : 1000,
          to      : 4000,
          type    : 'double',
          step    : 1,
          prefix  : '$',
          prettify: false,
          hasGrid : true
        })
        $('#range_2').ionRangeSlider()

        $('#range_5').ionRangeSlider({
          min     : 0,
          max     : 10,
          type    : 'single',
          step    : 0.1,
          postfix : ' mm',
          prettify: false,
          hasGrid : true
        })
        $('#range_6').ionRangeSlider({
          min     : -50,
          max     : 50,
          from    : 0,
          type    : 'single',
          step    : 1,
          postfix : '°',
          prettify: false,
          hasGrid : true
        })

        $('#range_4').ionRangeSlider({
          type      : 'single',
          step      : 100,
          postfix   : ' light years',
          from      : 55000,
          hideMinMax: true,
          hideFromTo: false
        })
        $('#range_3').ionRangeSlider({
          type    : 'double',
          postfix : ' miles',
          step    : 10000,
          from    : 25000000,
          to      : 35000000,
          onChange: function (obj) {
            var t = ''
            for (var prop in obj) {
              t += prop + ': ' + obj[prop] + '\r\n'
            }
            $('#result').html(t)
          },
          onLoad  : function (obj) {
            //
          }
        })
      })
    </script>
  @endsection
@endsection
