<template>
    <div class="container">
      <div class="row">
        <div class="col-md-11 mx-auto">
          <div class="card">
            <div class="card-header bg-indigo" style="background: #6574CD">
              <h3 class="card-title bg-indigo text-light">Point Table</h3>

              <div class="card-tools">

                <button type="button" class="btn btn-success" @click="newModal">Add new <i class="fas fa-user-plus"></i> </button>
              </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body table-responsive p-0">
              <table class="table table-hover">
                <tbody><tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Description</th>
                  <th>Location</th>
                  <th>Contact Person</th>
                  <th>Mobile Number</th>
                  <th>Action</th>
                </tr>
                <tr v-for="point in points.data" :key="point.id">
                  <td> {{ point.id }} </td>
                  <td> {{ point.name  }} </td>
                  <td> {{ point.description  }} </td>
                  <td> {{ point.location  }} </td>
                  <td> {{ point.contact_person  }} </td>
                  <td> {{ point.phone_number  }} </td>
                  <td>
                    <a href="#" @click="editModal(point)">
                      <i class="fas fa-edit green" style="font-size: 25px;"></i>
                    </a> &nbsp;
                    <a href="#" @click="deletePoint(point.id)">
                      <i class="fas fa-trash red" style="font-size: 25px;"></i>
                    </a>
                  </td>
                </tr>

              </tbody></table>
            </div>
            <div class="card-footer">
              <pagination :data="points" @pagination-change-page="getResults"></pagination>
            </div>
            <!-- /.card-body -->
          </div>
          <!-- /.card -->
        </div>
      </div>
      <!-- Modal -->
      <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 v-show="editMode" class="modal-title" id="exampleModalLabel">Update Point</h5>
              <h5 v-show="!editMode" class="modal-title" id="exampleModalLabel">Add new Point</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>

            <form @submit.prevent="editMode ? updatePoint() : createPoint()">

              <div class="modal-body">

                <!-- point name -->
                <div class="form-group">
                  <input v-model="form.name" type="text" name="name" placeholder="Point Name"
                    class="form-control form-control-sm" :class="{ 'is-invalid': form.errors.has('name') }">
                  <has-error :form="form" field="name"></has-error>
                </div>

                <!-- contact person -->
                <div class="form-group">
                  <input v-model="form.contact_person" type="text" name="contact_person" placeholder="Contact Person" class="form-control form-control-sm">
                </div>

                <!-- mobile number -->
                <div class="form-group">
                  <input v-model="form.phone_number" type="text" name="phone_number" placeholder="Contact Person" class="form-control form-control-sm">
                </div>

                <!-- desctiption -->
                <div class="form-group">
                  <textarea v-model="form.description" class="form-control form-control-sm" title="Not Required" placeholder="Write description" rows="3"></textarea>
                </div>

                <!-- desctiption -->
                <div class="form-group">
                  <textarea v-model="form.location" class="form-control  form-control-sm" title="Not Required" placeholder="Write point location/address" rows="3" cols="5"></textarea>
                </div>



              </div>


              <div class="modal-footer">
                <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Close</button>
                <button v-show="editMode" type="submit" class="btn btn-success btn-sm">Update</button>
                <button v-show="!editMode" type="submit" class="btn btn-primary btn-sm">Create</button>
              </div>
            </form>
          </div>
        </div>
      </div> <!-- End modal -->

    </div>
</template>



<script>

    export default {
        data() {
          return {
            editMode: false,
            points: {},
            form: new Form({
              id: '',
              name: '',
              contact_person: '',
              phone_number: '',
              description: '',
              location: '',
            })
          }
        },
        methods: {
          getResults(page = 1) {
            axios.get('api/point?page=' + page)
            .then(response => {
            this.points = response.data;
          });
        },
          editModal(point) {
            this.editMode = true;
            this.form.reset();
            $("#addModal").modal('show');
            this.form.fill(point);
          },
          newModal() {
            this.editMode = false;
            this.form.reset();
            $("#addModal").modal('show');
          },
          deletePoint(id) {
            swal.fire({
              title: 'Are you sure?',
              type: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
              if (result.value) {
                this.form.delete('api/point/'+id).then(()=>{
                  toast.fire({
                    type: 'success',
                    title: 'Point Deleted Successfully'
                  });
                  Fire.$emit('AfterAction');
                }).catch(()=>{
                  swal.fire(
                    '',
                    'Point Deleted Failed',
                    'error'
                  )
                })

              }
            })
          },
          loadPoints() {
            axios.get("api/point").then(({ data }) => (this.points = data));
          },
          updatePoint() {
            this.$Progress.start();
            this.form.put('api/point/' + this.form.id)
            .then(()=>{
                // success action
                Fire.$emit('AfterAction');
                $("#addModal").modal('hide');
                toast.fire({
                  type: 'success',
                  title: 'Point updated successfully'
                })
                this.$Progress.finish();
            }).catch(()=>{
              // failed response
              swal.fire(
                '',
                'Point Update Failed',
                'error'
              )
              this.$Progress.fail();
            });
          },
          createPoint() {
            this.$Progress.start();
            this.form.post('api/point')
            .then(()=> {
              Fire.$emit('AfterAction');
              $("#addModal").modal('hide');
              toast.fire({
                type: 'success',
                title: 'Point created successfully'
              })

              this.$Progress.finish();
            })
            .catch(()=>{
              this.$Progress.fail();
            })

          }
        },
        created() {
          this.loadPoints();
          Fire.$on('AfterAction', () => {
            this.loadPoints();
          });
        }
      }
</script>
