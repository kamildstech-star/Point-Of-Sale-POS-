@extends('layouts.master')

@section('title')
    Stock Index
@endsection
@section('css')   
		<link href="{{ asset('asset/css/ledgerIndex.css') }}" rel="stylesheet">
@endsection
@php
$ledgerss = 0;

     $ledgers = $bussinessDealer->ledger;
     $creditTotal = 0;
     $debitTotal = 0;
@endphp
@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Ledger</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Ledger</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add Ledger</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form action="{{ route('ledger.create') }}" method="POST" >
              <div class="modal-body">
                    @csrf
                        <div class="form-group">
                            <label for="name">Amount</label>
                            <input type="number" name="amount" class="form-control" id="amount" placeholder="Amount">
                            <input type="hidden" name="id" class="form-control" id="id" placeholder="id" value="{{$id ? $id  : 0}}">
                            @error('amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Description</label>
                            <textarea class="form-control" name="description" id="description" cols="30" rows="5"></textarea>
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select class="form-control" name="type">
                                <option value="0">Select Type</option>
                                <option value="1">Debit</option>
                                <option value="2">Credit</option>
                            </select>
                            @error('type')
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
            @if(session()->has('message'))
                <div class="alert alert-primary">
                    {{ session()->get('message') }}
                </div>
            @endif
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body d-flex" style="background-color: #1d1d50">
                           <div class="col-md-10">
                               <h4 class="text-light" >{{ $bussinessDealer->name }}</h4>
                           </div>
                           <div class="col-md-2 " style="text-align:end;">
                                   <a href="{{ route('stock.show')}}" class="btn" style="background-color: rgb(223, 197, 148);font-weight:600" data-toggle="modal" data-target="#exampleModal" >Add Ledger</a>
                               </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped table-hover">
                                <thead style="background-color: #1d1d50;color:white;">
                                    <tr>
                                        <th class="date-th">Date</th>
                                        <th class="description-th">Description</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                        <th class="balance-th">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($ledgers as $ledger)
                                @php
                                   $creditTotal += $ledger->credit;
                                   $debitTotal += $ledger->debit;
                                   $ledgerss = $ledger->balance;

                                @endphp
                                        <tr>
                                           
                                            <td>{{ date('d-m-Y',strtotime($ledger->created_at)) }}</td>
                                            <td>{{ $ledger->description }}</td>
                                            <td>{{ $ledger->debit}}</td>
                                            <td>{{ $ledger->credit }}</td>
                                            <td>{{ $ledger->balance }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                           <td colspan="2"><b>Total : </b></td>
                                           <td>{{ $debitTotal}}</td>
                                           <td>{{ $creditTotal }}</td>
                                           <td>{{ $ledgerss  }}</td>
                                       </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection