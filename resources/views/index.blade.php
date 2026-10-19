@extends('layouts.master')

@section('title')
  Best Uniform | Dashboard
@endsection

@section('content')

  <div class="wrapper">
    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
      <img class="animation__shake" src="{{asset('asset/img/Logo2.png') }}" alt="AdminLTELogo" height="60" width="60">
    </div>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Dashboard</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <div class="row"> 
            <div class="col-lg-4 col-4">
              <!-- small box -->
              <div class="small-box bg-info">
                <div class="inner">
                  <h3>{{ $totalOrders }}</h3>
                  <p>Orders</p>
                </div>
                <a href="{{ route('orders.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
              </div>
            </div>
            <div class="col-lg-4 col-4">
              <!-- small box -->
              <div class="small-box bg-success">
                <div class="inner">
                  <h3>{{ $totalQuantity }}</h3>
                  <p>Inventory</p>
                </div>
                <a href="{{ route('stocks.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
              </div>
            </div>
            <div class="col-lg-4 col-4">
              <!-- small box -->
              <div class="small-box bg-warning">
                <div class="inner">
                <h3>{{ $dailySale }}</h3>
                  <p>Sale</p>
                </div>
                <a href="{{ route('orders.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
              </div>
            </div>
          </div>
          <div class="row">
            <!-- Left col -->
            <section class="col-lg-6 connectedSortable">
              <div class="row">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header">
                      <h5 class="card-title">Monthly Recap Report</h5>
                      <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                          <i class="fas fa-minus"></i>
                        </button>
                        <div class="btn-group">
                          <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-wrench"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right" role="menu">
                            <a href="#" class="dropdown-item">Action</a>
                            <a href="#" class="dropdown-item">Another action</a>
                            <a href="#" class="dropdown-item">Something else here</a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">Separated link</a>
                          </div>
                        </div>
                        <button type="button" class="btn btn-tool" data-card-widget="remove">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-12">
                          <p class="text-center">
                            <strong>Sales: 1 Jan, 2014 - 30 Jul, 2014</strong>
                          </p>
                          <div class="chart">
                            <canvas id="salesChart" height="180" style="height: 180px;"></canvas>
                          </div>
                        </div>
                      </div>
                      <!-- /.row -->
                    </div>
                  </div>
                  <!-- /.card -->
                </div>
              </div>
              <!-- TO DO List -->
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">
                    <i class="ion ion-clipboard mr-1"></i> To Do List
                  </h3>
                </div>
                <div class="card-body">
                  <ul class="todo-list" data-widget="todo-list">
                      @foreach($todoList as $task)
                          <li>
                              <span class="handle">
                                  <i class="fas fa-ellipsis-v"></i>
                                  <i class="fas fa-ellipsis-v"></i>
                              </span>
                              <div class="icheck-primary d-inline ml-2">
                                  <input  type="checkbox" value="" 
                                          name="todo{{ $task->id }}"
                                          id="todoCheck{{ $task->id }}"
                                          class="task-checkbox"
                                          data-id="{{ $task->id }}" {{ $task->completed ? 'checked' : '' }} >
                                  <label for="todoCheck{{ $task->id }}"></label>
                              </div>
                              <span class="text editDescription">{{ $task->description }}</span>
                              <div class="tools">
                                  <i class="fas fa-edit editTask"></i>
                              </div>
                          </li>
                      @endforeach
                  </ul>
                  <!-- Pagination Links -->
                  <div class="mt-3">
                    {{ $todoList->links() }}
                  </div>
                </div>
                <div class="card-body">
                  <ul class="todo-list" id="todo-list" data-widget="todo-list"></ul>
                  <!-- Form initially hidden -->
                    <form id="todo-form" style="display:none;">
                      @csrf
                      <div class="col-md-10">
                        <input type="hidden" name="id" id="getid">
                        <input type="text" id="description" name="description" placeholder="Enter a new task">
                        <button type="submit" class="btn-sm btn-primary">Save</button>
                      </div>
                    </form>
                </div>
                <div class="card-footer clearfix">
                  <button type="button" class="btn btn-primary float-right addBtn"><i class="fas fa-plus"></i> Add item</button>
                </div>
              </div>
              <!-- /.card -->
            </section>
            <section class="col-lg-6 connectedSortable">
              <div class="card bg-gradient-success">
                <div class="card-header border-0">
                  <h3 class="card-title">
                    <i class="far fa-calendar-alt"></i>
                    Calendar
                  </h3>
                  <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" data-card-widget="collapse">
                      <i class="fas fa-minus"></i>
                    </button>
                  </div>
                </div>
                <div class="card-body pt-0">
                  <div id="calendar" style="width: 100%"></div>
                </div>
              </div>
              <!-- TABLE: LATEST ORDERS -->
              <div class="card">
                <div class="card-header border-transparent">
                  <h3 class="card-title">Latest Orders</h3>
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
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table m-0">
                      <thead>
                        <tr>
                          <th>Order ID</th>
                          <th>Customar Name</th>
                          <th>Discount</th>
                          <th>SubTotal</th>
                          <th>Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach ($orders as $order)  
                          <tr>
                            <td>{{ $order->id  }}</a></td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $order->discount }}</td>
                            <td>{{ $order->subtotal }}</td>
                            <td>{{ $order->total }}</td>
                          </tr>                      
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                  <!-- /.table-responsive -->
                </div>
                <!-- /.card-body -->
                <div class="card-footer clearfix">
                  <a href="{{ route('order.show') }}" class="btn btn-sm btn-info float-left">Place New Order</a>
                  <a href="{{ route('orders.index') }}" class="btn btn-sm btn-secondary float-right">View All Orders</a>
                </div>
                <!-- /.card-footer -->
              </div>   
              </section>
            <!-- right col -->
          </div>
        </div>
      </section>
    </div>
  </div>

  @section('script')

      <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
      <script src="{{asset('asset/js/pages/dashboard2.js')}}"></script>
      <!-- AdminLTE App -->
      <script src="{{asset('asset/js/pages/dashboard.js')}}"></script>
      {{-- jQuery UI 1.11.4 --}}
      <script src="{{ asset('asset/jquery-ui/jquery-ui.min.js') }}"></script>
      <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
      <script>
        $.widget.bridge('uibutton', $.ui.button)
      </script> 
      <!-- ChartJS -->
      <script src="{{ asset('asset/chart.js/Chart.min.js') }}"></script>

      <script src="{{ asset('asset/jqvmap/jquery.vmap.min.js') }}"></script>
      <script src="{{ asset('asset/jqvmap/maps/jquery.vmap.usa.js') }}"></script>
      <!-- jQuery Knob Chart -->
      <script src="{{ asset('asset/jquery-knob/jquery.knob.min.js') }}"></script>
      <!-- daterangepicker -->
      <script src="{{ asset('asset/moment/moment.min.js') }}"></script>
      <script src="{{ asset('asset/daterangepicker/daterangepicker.js') }}"></script>
      <!-- Tempusdominus Bootstrap 4 -->
      <script src="{{ asset('asset/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
      <!-- Summernote -->
      <script src="{{ asset('asset/summernote/summernote-bs4.min.js') }}"></script>
      <!-- overlayScrollbars -->
      <script src="{{ asset('asset/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
      <script>
        var salesChart = {!! json_encode($salesChart) !!};
        var salesChartCanvas = $('#salesChart').get(0).getContext('2d');
        var salesChartData = {
            labels: salesChart.month,
            datasets: [{
                label: 'Sales',
                backgroundColor: 'rgba(60,141,188,0.9)',
                borderColor: 'rgba(60,141,188,0.8)',
                pointRadius: false,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: salesChart.data
            }]
        };
        var salesChartOptions = {
            maintainAspectRatio: false,
            responsive: true,
            legend: {
                display: false
            },
            scales: {
                xAxes: [{
                    gridLines: {
                        display: false
                    }
                }],
                yAxes: [{
                    gridLines: {
                        display: false
                    }
                }]
            }
        };
        // Initialize the chart
        var salesChartInstance = new Chart(salesChartCanvas, {
            type: 'line',
            data: salesChartData,
            options: salesChartOptions
        });
        // Submit form via AJAX
        $('#todo-form').on('submit', function(e) {
          e.preventDefault();
          // Get the value of the description input
          let description = $('#description').val();
            // Make sure the description is not empty
            if (description.trim() === "") {
              alert("Please enter a task description");
              return;
            }
            // Make the AJAX request
            $.ajax({
              url: '{{ route("todolist") }}',  // Your route URL for the form submission
              method: 'POST',
              data: {
                      _token: '{{ csrf_token() }}',  // Include the CSRF token
                      description: description       // Include the task description
                    },
              success: function(response) {
                if (response.success) {
                  // If the request is successful, add the new task to the list
                  let newItem = `
                                <li class="todolist" data-id="${response.task.id}">  <!-- Add data-id here -->
                                  <span class="handle">
                                    <i class="fas fa-ellipsis-v"></i>
                                    <i class="fas fa-ellipsis-v"></i>
                                  </span>
                                  <div class="icheck-primary d-inline ml-2">
                                    <input type="checkbox" value="" name="newTodo">
                                    <label for="newTodo"></label>
                                  </div>
                                  <span class="text">${description}</span>
                                  <div class="tools">
                                    <i class="fas fa-edit"></i>
                                  </div>
                                </li>`;
                    // Append the new item to the list
                    $('#todo-list').append(newItem);                              
                    // Clear the input field
                    $('#description').val('');                              
                    // Remove the form after successful submission
                    $('#todo-form').remove();
                } else {
                    alert('Something went wrong. Please try again.');
                }
              },
              error: function() {
                alert('An error occurred. Please try again.');
              }
            });
        });

        // Click event for editing tasks
        $('.editTask').click(function() {
          // Get the task ID from the checkbox ID (it's part of the checkbox's ID attribute)
          let taskId = $(this).closest('li').find('input[type="checkbox"]').attr('id').replace('todoCheck', '');
          // Get the task description from the clicked task's description span
          let taskDescription = $(this).closest('li').find('.editDescription').text();          
          // Show the form with the current task description
          $('#todo-form').show();
          $('#description').val(taskDescription); 
          $('#getid').val(taskId);
        });

        // Form submission for editing tasks
        $('#todo-form').submit(function(event) {
            event.preventDefault();
            // Get the task ID and description from the form
            let taskId = $('#getid').val(); 
            let taskDescription = $('#description').val(); 
            // Perform the AJAX request to update the task
            $.ajax({
                url: '/updateTaskStatus/' + taskId, 
                method: 'PUT',
                data: {
                    _token: $('input[name="_token"]').val(), 
                    description: taskDescription, // The updated task description
                },
                success: function(response) {
                    if (response.success) {
                        // Update the task description in the UI without adding a new task
                        $(`input#todoCheck${taskId}`).closest('li').find('.editDescription').text(taskDescription);
                        // Hide the form after successful update
                        $('#todo-form').hide();
                    } else {
                        alert('Failed to update the task.');
                    }
                },
                error: function() {
                    alert('An error occurred while updating the task.');
                }
            });
        });

        $('.addBtn').click(function() {
            $('#todo-form').show();
            $('#description').val('');  // Clear the form for a new task
            $('#todo-form').data('task-id', ''); // Clear the task ID for new task submission
            $('#description').focus();  // Focus on the input
        });

        $('.task-checkbox').change(function() {
          let taskId = $(this).data('id');
          let isChecked = $(this).is(':checked');
          // Send AJAX request to update task status
          $.ajax({
            url: '{{ route("task.updateStatus") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}', // Include CSRF token
                id: taskId,
                completed: isChecked ? 1 : 0
            },
            success: function(response) {
              if(response.success) {
                toastr.success('Task Updated Successfully.');
              } else {
                toastr.error('Task Not Found.');
              }
            },
            error: function(xhr) {
              toastr.error('An error occurred while updating the task.');
            }
          });
        });

      </script>     
  @endsection
@endsection

