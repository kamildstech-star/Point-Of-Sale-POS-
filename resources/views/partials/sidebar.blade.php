<nav class="main-header navbar navbar-expand" style="background-color: #1d1d50;padding:10px">
  <ul class="navbar-nav ml-auto">
    <li class="nav-item" style="margin-right:20px;">
      <a href="{{ route('order.show') }}">
        <button class="btn" style="background-color: rgb(223, 197, 148);font-weight:600" type="submit">Create Order</button>
      </a>
    </li>
    <li class="nav-item" style="margin-right:20px;">
      <a href="{{ route('return.show') }}">
        <button class="btn" style="background-color: rgb(223, 197, 148);font-weight:600" type="submit">Create Return</button>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ route('logoutpost') }}">
        <button class="btn" style="background-color: rgb(223, 197, 148);font-weight:600" type="submit">LogOut</button>
      </a>
    </li>
  </ul>
</nav>

<aside class="sidebar main-sidebar sidebar-dark-primary elevation-8" style="background-color: #1d1d50;">
  <a href="{{ route('dashboard') }}" class="brand-link">
    <span class="brand-text font-weight-bold">Best Uniform</span>
  </a>
  <!-- Sidebar -->
  <div class="sidebar" style="">
    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <!-- Add icons to the links using the .nav-icon class with font-awesome or any other icon font library -->
        <li class="nav-item menu-open">
          <a href="#" class="nav-link active">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>
              Dashboard
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('dashboard') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Dashboard</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Roles
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('roles.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Roles</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('roles.show') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Add Role</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Permission
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('permission.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Permission</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('permission.show') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Add Permission</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Users
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('users.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Users</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('user.show') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Add User</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Schools
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('school.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Schools</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('school.show') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Add School</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Products
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('product.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Products</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('product.show') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Add Product</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Sizes
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('sizes.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Sizes</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('size.show') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Add Size</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Stocks
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('stocks.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Stocks</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('stock.show') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Add stock</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Orders
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('orders.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Orders</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('order.show') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Add Order</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Returns
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('returns.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Returns</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('return.show') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Return Order</p>
              </a>
            </li>
          </ul>
        </li>
       <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Reports
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview"> 
            <li class="nav-item">
               <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>
                  Sales Reports
                    <i class="fas fa-angle-left right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href={{ route("dailysales") }} class="nav-link">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Daily Sale Report</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('monthlySales') }}" class="nav-link">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Monthly Sale Report</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{ route('anuallysales')}}" class="nav-link">
                      <i class="far fa-circle nav-icon"></i>
                      <p>Anually Sale Report</p>
                    </a>
                  </li>
                </ul>
              </li>
            </li>
            <li class="nav-item">
              <a href="{{ route('Stocks.report') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Stocks Reports</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('ledger.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Ledger</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('returnSales') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Return Report</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Account
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('dailyAccount') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Daily Account</p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Business Dealer
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('businessDealer.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Business Dealer </p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{route('businessDealer.show')  }}" class="nav-link">
              
                <i class="far fa-circle nav-icon"></i>
                <p>Add Business Dealer </p>
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>
              Settings
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="{{ route('settings.index') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Settings</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('setting.show') }}" class="nav-link">
                <i class="far fa-circle nav-icon"></i>
                <p>Add Settings</p>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
    <!-- /.sidebar -->
</aside>
