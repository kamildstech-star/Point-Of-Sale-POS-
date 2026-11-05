@extends('layouts.master')
@section('title')
    Daily Account
@endsection
@section('css')   
	<link href="{{ asset('asset/css/stock.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Daily Account</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Daily Account</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add Expences</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form action="{{ route('dailyOutcome') }}" method="POST">
              <div class="modal-body">
                    @csrf
                        
                        <div class="form-group">
                                <label for="provider">Providers</label>
                                <select class="form-control" name="provider">
                                        <option value="0">Select Provider</option>
                                    @php
                                    foreach ($providers as $provider) {
                                        echo "<option value=".$provider->id.">".$provider->name."</option>";
                                        }
                                    @endphp
                                </select>
                                @error('type')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Amount</label>
                            <input type="number" name="amount" class="form-control" id="amount" placeholder="Amount">
                            @error('amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Description</label>
                            <textarea class="form-control" name="description" id="description" cols="30" rows="5"></textarea>
                            <input type="hidden" name="dates" class="form-control dates" id="dates" value="{{ $date != null ? $date :date('Y-m-d')}}" >
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                      </div>
                      <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Submit</button>
              </div>
              </form>

            </div>
          </di>
        </div>
        </div>
        <div class="modal fade" id="exampleModal2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add Expences</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form action="{{ route('dailyIncome') }}" method="POST">
              <div class="modal-body">
                    @csrf
                        
                        <div class="form-group">
                            <label for="party">Party</label>
                            <select class="form-control" name="party">
                                    <option value="0">Select Party</option>
                                @php
                                   foreach ($parties as $party) {
                                    echo "<option value=".$party->id.">".$party->name."</option>";
                                    }
                                @endphp
                            </select>
                            @error('type')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                     
                        <div class="form-group">
                            <label for="name">Amount</label>
                            <input type="number" name="amount" class="form-control" id="amount" placeholder="Amount">
                            @error('amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Description</label>
                            <input type="hidden" name="dates" class="form-control dates" id="dates" value="{{ $date != null ? $date :date('Y-m-d')}}" >
                            <textarea class="form-control" name="description" id="description" cols="30" rows="5"></textarea>
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                      </div>
                      <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Submit</button>
              </div>
              </form>

            </div>
          </di>
        </div>
        </div>
        <div class="container">
            <div class="card ">
                <div class="card-header" >
                    <h3 class="card-title mt-2">Daily Account</h3>  
                </div>
                <form action="{{ route('dailyAccount.create') }}" method="POST" id="stockform">
                    @csrf
                    <div class="card-body"> 
                        <div class="container">
                            <div class="row mb-3"  style="font-size: 20px;">
                                <div class="col-md-6">
                                    <div class="form-group" >
                                        <label for="date" class="font-weight-bold">Date :</label>
                                        <input type="date" name="date" class="form-control date" id="date" value="{{ $date != null ? $date :date('Y-m-d')}}" >
                                        <input type="hidden" class="da_id" value="{{$da_id}}" name="da_id" id="da_id">
                                    </div>
                                    <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                        <label for="subtotal" class="font-weight-bold">In Cash :</label>
                                        <input type="hidden" class="in_price" value="{{$closing_balance}}" name="inCash">
                                        <span class="inPrice" name="showprice">{{$closing_balance}}</span>
                                    </div>
                                    <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                        <label for="discount" class="font-weight-bold">Sale :</label>
                                        <input type="hidden" value="{{$total_sale}}" class="in_price" name="sale">
                                        <span class="showdiscount inPrice">{{$total_sale}}</span>
                                    </div>
                                    @foreach ($ledgers as $ledger )
                                    @if ($ledger->debit != null)
                                    <div class="form-group" style="border-bottom: 1px solid #ced4da;display:flex;justify-content:space-between;">
                                        <div class="div">
                                            <label for="discount" class="font-weight-bold">{{$ledger->name}} :</label>
                                            <input type="hidden" value="{{$ledger->l_id}}" name="in[]" class="in_price">
                                            <span class="inPrice">{{$ledger->debit}}</span>
                                        </div>
                                           <a href="{{route('ledger.destroy',['id'=>$ledger->l_id])}}"><i class="fas fa-trash ml-5 text-danger" style="{{ $isEditable == false ? 'display:none;': '' }}"></i></a>
                                    </div>
                                    
                                    @endif
                                    @endforeach
                                    <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                        <label for="total" class="font-weight-bold">Total :</label>
                                        <input type="hidden" value="0" name="total">
                                        <span class="showtotalprice" id="in_prices">0</span>
                                    </div>
                                    <button type="button" class="btn btn-success sm-btn add" data-toggle="modal" data-target="#exampleModal2" ><b>+</b></button>

                                </div>
                                <div class="col-md-5 ">
                                    <div class="form-group">
                                        <label for="name">Remaining :</label>
                                        <input type="text" name="remaing" class="form-control " placeholder="Remaining" id="remaining">
                                    </div>
                                </div>
                                <div class="col-md-1 ">
                                </div>
                            </div>
                            <hr>
                            <div class="row mt-4 mb-4">
                              <div class="col-md-8" style="border-right:1px solid #cad2c5">
                                <div class="table-responsive p-0">
                                    <table class="table table-hover text-nowrap">
                                        <thead style="background-color: #1d1d50;color: white;">
                                            <tr >
                                                <th>
                                                  <div class="d-flex" style="display:flex;justify-content:space-between;">
                                                    <h4>Out</h4>
                                                    <button type="button" class="btn btn-success sm-btn add ml-5 " data-toggle="modal" data-target="#exampleModal" ><b>+</b></button>

                                                  </div>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                 $total_out = 0;
                                            @endphp
                                            @foreach ($ledgers as $ledger)
                                            @if ($ledger->credit != null)
                                            @php
                                             $total_out += $ledger->credit;
                                            @endphp
                                                    <tr>
                                                        <td >
                                                            <input type="hidden" value="{{$ledger->l_id}}" name="out[]" class="out">
                                                            <div class="d-flex" style="display:flex;justify-content:space-between;">
                                                                <span style="color:#6b705c;font-size:20px;"><b>{{$ledger->name}} : </b>  {{$ledger->credit}}</span>
                                                                <a href="{{route('ledger.destroy',['id'=>$ledger->l_id])}}" style="{{ $isEditable == false ? 'display:none;': '' }}"
                                                                ><i class="fas fa-trash ml-5 text-danger"></i></a>

                                                            </div>
                                                        </td>
                                                    </tr>
                                            @endif
                                            @endforeach
                                                    <tr>
                                                        <td><span style="color:#6b705c;font-size:20px"><b>{{$total_out}} :</span> Total</b></td>
                                                    </tr>
                                        </tbody>
                                    </table>
                                  </div>
                              </div>
                              <div class="col-md-4"  style="font-size: 20px;">
                                    <div class="form-group" style="border-bottom: 1px solid #ced4da" >
                                        <label for="sale" class="font-weight-bold">Total In :</label>
                                        <input type="hidden" value="" class="total_in" name="total_in" id="total_in">
                                        <span class="showprice"  style="color:#6b705c" name="showprice"><b id="total_in_span"></b></span>
                                    </div>
                                    <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                        <label for="subtotal" class="font-weight-bold">Total Out :</label>
                                        <input type="hidden" value="{{$total_out}}" class="total_out" name="total_out" id="total_out">
                                        <span class="showprice" style="color:#6b705c" name="showprice"><b>{{$total_out}}</b></span>
                                    </div>
                                    <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                        <label for="discount" class="font-weight-bold">Closing Balance :</label>
                                        <input type="hidden" value="0" class="total_out" name="closing_balance" id="closing_balance">
                                        <span class="showdiscount" style="color:#6b705c"><b class="closing_balance"></b></span>
                                    </div>
                                </div>
                        </div>
                    </div>  
                    <div class="card-footer">
                        <button type="submit" class="btn" 
                        @php
                        if($isEditable == false){
                           echo 'disabled';
                        }
                        @endphp
                        >Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@endsection

@section('script')
    <script>
        
        $total_prices = 0;
        $( document ).ready(function() {
            var in_prices = $('.inPrice').each(function(index) {
                var el =$(this).text();
                $total_prices +=  parseInt(el)
            });
            $('#in_prices').text($total_prices)
            $('#total_in_span').text($total_prices)
            $('#total_in').val($total_prices)
            total_in = $('#total_in').val();
            total_out = $('#total_out').val();
            closing_balance = parseInt(total_in) - parseInt(total_out)
            $('#closing_balance').val(closing_balance)
            $('.closing_balance').text(closing_balance)

            $('#date').on('change',function(){
                date = $('#date').val();
                location.href = "http://localhost:8000/dailyAccount?date="+date;
            })

        });
    </script>
@endsection
