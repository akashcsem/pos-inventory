
<template>
  <div>
    <div class="row">
      <div class="col-12 mb-3 text-right">
        <span
          class="float-right px-3 print-invoice"
          v-show="mode=='invoice'"
          @click="printInvoice('invoice')"
        >Print</span>

        <span
          class="float-right px-2 purchase-list"
          v-show="mode=='invoice'"
          @click="mode= 'purchase'"
        >Purchase Return List</span>
      </div>
      <!-- Display all purchase in table -->
      <div class="col-12 mx-auto" v-if="mode != 'invoice'">
        <!-- display all purchase -->
        <div class="card">
          <div class="card-header" style="background: #563D7C">
            <!-- purchase heading -->
            <h3 class="card-title text-light">Purchase Return List</h3>

            <div class="card-tools">
              <table>
                <tr>
                  <td>
                    <!-- Product Search -->
                    <div class="input-group float-right">
                      <input
                        type="text"
                        class="form-control text-light"
                        style="background: #563D7C"
                        placeholder="Search Product"
                      />
                      <div class="input-group-append">
                        <span class="btn btn-outline-light">Search</span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <router-link
                      to="purchase-return"
                      class="ml-3 btn btn-outline-warning"
                    >Purchase Return</router-link>
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <!-- /.card-header -->
          <div class="card-body p-0">
            <table class="table table-hover">
              <tbody>
                <tr class="bg-primary">
                  <th>Return No</th>
                  <th>Supplier</th>
                  <th>Return By</th>
                  <th>Total Quantity</th>
                  <th>Total Price</th>
                  <th>Date</th>
                  <th>Action</th>
                </tr>

                <tr v-for="(purchase, index) in purchases.data" :key="index">
                  <td class="greenHover" @click="showInvoice(purchase)">{{ purchase.pur_rtn_no }}</td>
                  <td style="text-transform: capitalize;">{{ purchase.supplier.name }}</td>
                  <td
                    style="text-transform: capitalize;"
                  >{{ purchase.user.first_name }} {{ purchase.user.last_name }}</td>
                  <td>{{ purchase.totalQty }}</td>
                  <td>{{ purchase.grandTotal | bdCurrency }} Tk.</td>
                  <td>{{ purchase.created_at | myDate }}</td>
                  <td>
                    <a href="#" @click="showInvoice(purchase)" title="View Invoice">
                      <i class="fas fa-eye" style="font-size: 20px;"></i>
                    </a>
                    <a href="#" @click="deletePurchaseReturn(purchase.pur_rtn_no)">
                      <i class="fas fa-trash red" style="font-size: 20px;"></i>
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- pagination -->
          <div class="card-footer">
            <pagination :data="purchases" @pagination-change-page="getResults"></pagination>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
      <!-- Display all purchase in table -->

      <!-- Invoice -->
      <div class="col-12 mb-3" id="invoice" v-else>
        <div class="card p-5">
          <div>
            <div class="row">
              <div class="col-md-6">
                <h4 class="font-weight-bold">Point Of Sale</h4>
                <p>
                  Ashkona, Airport,
                  <br />
                  <b>Dhaka, Bangladesh</b>
                  <br />
                  <b>01976829262</b>
                </p>
              </div>
              <div class="col-md-6 text-right">
                <h4 class="font-weight-bold text-primary" style="display: inline-block">INVOICE</h4>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <h4 class="bg-primary p-1 pl-3">Return To</h4>
                <strong class="d-block">Name: {{ invoice.supplier.name }}</strong>
                <h6 class="d-block">Company Name: {{ invoice.supplier.company_name }}</h6>
                <h6 class="d-block">Address: {{ invoice.supplier.address }}</h6>
                <h6 class="d-block">Email: {{ invoice.supplier.email }}</h6>
                <h6 class="d-block">Phone Number: {{ invoice.supplier.phone_number }}</h6>
              </div>

              <div class="col-md-4 ml-auto">
                <div class="row">
                  <div class="col-md-12">
                    <h4 class="bg-primary p-1 pl-3" style="letter-spacing: 1.5px">
                      Invoice#
                      <span class="float-right mr-4">Date</span>
                    </h4>
                    <h6 class="px-2">
                      {{ invoice.pur_rtn_no }}
                      <span
                        class="float-right"
                      >{{ invoice.created_at | myDate}}</span>
                    </h6>
                  </div>

                  <div class="col-md-12 mt-4">
                    <h4 class="bg-primary p-1 pl-3" style="letter-spacing: 1.5px">Returned by</h4>
                    <h6 class="px-2">
                      <strong>
                        {{ invoice.user.first_name | capitalize }}
                        {{ invoice.user.last_name | capitalize }}
                      </strong>
                      <small>({{ invoice.user.role | capitalize }})</small>
                    </h6>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mx-2 mt-5">
              <table class="table table-sm mb-0">
                <tr>
                  <th>Sln</th>
                  <th>Product Name</th>
                  <th>Quantity</th>
                  <th>Price</th>
                  <th class="text-right pr-4">Total</th>
                </tr>

                <tr v-for="(item, c) in invoice.purchase_return_items" :key="c">
                  <td>{{ c+1 }}</td>
                  <td>{{ item.product.name }}</td>
                  <td>{{ item.quantity }}</td>
                  <td>{{ item.price | bdCurrency }} Tk.</td>
                  <td
                    class="text-right pr-4"
                  >{{ Math.ceil(item.quantity * item.price) | bdCurrency }} Tk.</td>
                </tr>

                <tr>
                  <td colspan="5"></td>
                </tr>
              </table>
            </div>
            <div class="row mx-2">
              <div class="ml-auto mr-3">
                <strong>Subtotal &nbsp; &nbsp; :</strong>
                {{ invoice.grandTotal |bdCurrency }} Tk.
                <br />
                <p style="border-bottom: 1px solid gray; margin-bottom: 0">
                  <strong>Discount &nbsp; &nbsp; :</strong>
                  {{ invoice.discount }} Tk.
                </p>
                <strong>GrandTotal :</strong>
                {{ (invoice.grandTotal - invoice.discount) | bdCurrency }} Tk.
                <br />
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- End Invoice -->
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      purchases: [],
      mode: "purchase-list",
      invoice: {},
      showingAddModal: false
    };
  },

  methods: {
    // results for pagination
    getResults(page = 1) {
      axios.get("api/purchase-return?page=" + page).then(response => {
        this.purchases = response.data;
      });
    },
    // delete purchase return
    deletePurchaseReturn(id) {
      swal
        .fire({
          title: "Are you sure?",
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Yes, delete it!"
        })
        .then(result => {
          if (result.value) {
            axios
              .get("api/purchase/return/delete/" + id)
              .then(() => {
                swal.fire("Deleted!", id + "Deleted successful.", "success");
              })
              .catch(() => {
                swal.fire("", "Return Deleted Failed", "error");
              });
          }
        });
    },
    // Individual purchase invoice
    showInvoice(purchase) {
      this.invoice = purchase;
      this.mode = "invoice";
    },
    printInvoice(el) {
      var restorepage = $("body").html();
      var printcontent = $("#" + el).clone();
      var enteredtext = $("#pirntInvoice").val();
      $("body")
        .empty()
        .html(printcontent);
      window.print();
    },
    // load data for table
    loadPurchases() {
      axios
        .get("api/purchase-return")
        .then(({ data }) => (this.purchases = data));
    }
  }, // end method

  created() {
    this.loadPurchases();
    Fire.$on("AfterAction", () => {
      this.loadPurchases();
    });
  }
};
</script>



<style scoped>
.green {
  background: white;
  color: green;
}
.greenHover:hover {
  color: green;
  cursor: pointer;
  font-size: 15px;
}

.print-invoice {
  background: green;
  padding: 5px;
  color: white;
  border-radius: 4px;
  cursor: pointer;
  margin-left: 10px;
}

.print-invoice:hover {
  font-weight: bold;
  background: #41b883;
}

.purchase-list {
  background: #3490dc;
  padding: 5px;
  color: white;
  border-radius: 4px;
  cursor: pointer;
}

.purchase-list:hover {
  font-weight: bold;
  background: #35495e;
}
</style>