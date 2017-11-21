import _ from 'lodash';
import React, { Component } from 'react';
import axios from 'axios';

import ImageFile from '../../components/ImageFile';

const defaultState = {
    id: 0,
    avatar: {value: {}, errors:[]},
    name: {value:'', errors:[]},
    email: {value:'', errors:[]},
    phone: {value: '', errors:[]},
    born: {value: '', errors:[]},
    gender: {value: '', errors:[]},
    admin: {value:false, errors:[]},
    password: {value:'', errors:[]},
    confirmPassword: {value:'', errors:[]},
    languages: {value:'', errors:[]},
    addressId:0,
    street: {value:'', errors:[]},
    number: {value:'', errors:[]},
    district: {value:'', errors:[]},
    country: {value:'', errors:[]},
    state: {value:'', errors:[]},
    city: {value:'', errors:[]},
    postalCode: {value:'', errors:[]},
    loadingCountry: false,
    countryList: [],
    loadingState: false,
    stateList: [],
    loadingCity: false,
    cityList: [],
    hasError: true,
}

const USER_URL = 'https://guideup.com.br/api/user';
const PLACE_URL = 'https://guideup.com.br/api/place';

class UserEdit extends Component {

config = {
    headers: {
    'Authorization': "Bearer " + localStorage.getItem('token'), 
    'Accept': 'application/json' 
    }
};

  constructor(props) {
    super(props);

    this.state = defaultState;
    
    const { id } = this.props.match.params;
    if(!id || id < 1) {
        return;
    }

    this.state.id = id;
  }

  componentDidMount() {
      this.fetchUser();
      this.fetchCountry();
  }

  fetchUser = () => {
      if(this.state.id < 1) return;

      axios.get(`${USER_URL}/${this.state.id}`, this.config)
        .then(response => {
            const user = response.data;
            user.address = user.address || {};

        this.setState({
            avatar: {...this.state.avatar, value: {id: 0, thumbnail_url: user.avatar_url }},
            name: {...this.state.name, value: user.name },
            email: {...this.state.name, value: user.email },
            phone: {...this.state.name, value: user.phone },
            born: {...this.state.born, value: user.born },
            gender: {...this.state.gender, value: user.gender },
            admin: {...this.state.admin, value: user.is_admin == 1 },
            addressId: {...this.state.addressId, value: user.address.id || 0 },
            street: {...this.state.street, value: user.address.street || '' },
            number: {...this.state.number, value: user.address.number || '' },
            district: {...this.state.district, value: user.address.district || '' },
            country: {...this.state.country, value: user.address.country_id || '' },
            state: {...this.state.state, value: user.address.state_id || '' },
            city: {...this.state.city, value: user.address.city_id || '' },
            postalCode: {...this.state.postalCode, value: user.address.postal_code || '' },
            languages: {...this.state.languages, value: _.map(user.languages, 'name').join(', ') || '' },
            hasError: false,
        });

        this.fetchState(user.address.country_id);
        this.fetchCity(user.address.state_id);
    })
    .catch(error => {
        console.log('error on get:', error);
        if(this.props.history.length < 1) {
            this.props.history.push('/user');
        }
        else {
            this.props.history.goBack();
        }
    })
  }

  fetchCountry = () => {
    this.setState({ loadingCountry: true })
    axios.get(`${PLACE_URL}?type=4&all=true`)
    .then(response => {
        this.setState({countryList: response.data});
        this.setState({ loadingCountry: false });
    })
  }

  fetchState = (country_id) => {
      if(country_id < 1) return;

    this.setState({ loadingState: true });
    axios.get(`${PLACE_URL}?type=3&country_id=${country_id}&all=true`).then(response => {
        this.setState({stateList: response.data});
        this.setState({ loadingState: false });
    })
  }
  
  fetchCity = (state_id) => {
    if(state_id < 1) return;

    this.setState({ loadingCity: true })
    axios.get(`${PLACE_URL}?type=2&state_id=${state_id}&all=true`)
    .then(response => {
        this.setState({cityList: response.data});
        this.setState({ loadingCity: false });
    })
  }

  handleSubmit = e => {
    e.preventDefault();
    // TODO - Save user if all fields valid
    if(!this.validateForm()) {
        return;
    }

    const loadingNotification = this.props.showNotification({message: '<div class="text-center"><i class="fa fa-refresh fa-spin"></i> Aguarde ...</div>', title: 'Salvando', level: 'info', autoDismiss: 0, dismissible: false, position: 'tc' })
    
    let data;

    if(this.state.avatar.value) {
        this.config.headers['content-type'] = 'multipart/form-data';

        data = new FormData();
        data.append('file', this.state.avatar.value, this.state.avatar.value.name);        
        data.append('name', this.state.name.value);
        data.append('email', this.state.email.value);
        data.append('phone', this.state.phone.value);
        data.append('born', this.state.born.value);
        data.append('gender', this.state.gender.value);
        data.append('languages', this.state.languages.value);
        data.append('address[street]', this.state.street.value);
        data.append('address[number]', this.state.number.value);
        data.append('address[district]', this.state.district.value);
        data.append('address[city_id]', this.state.city.value);
        data.append('address[postal_code]', this.state.postalCode.value);
        data.append('is_admin', this.state.admin.value);
    }
    else {
        data = {
            name: this.state.name.value,
            email: this.state.email.value,
            phone: this.state.phone.value,
            born: this.state.born.value,
            gender: this.state.gender.value,
            languages: this.state.languages.value,
            street: this.state.street.value,
            number: this.state.number.value,
            district: this.state.district.value,
            country_id: this.state.country.value,
            state_id: this.state.state.value,
            city_id: this.state.city.value,
            is_admin: this.state.admin.value,
        }
    }

    let request = null;
    if(this.state.id && this.state.id > 0) {
        data.append('_method','PUT');
        request = axios.post(`${USER_URL}/${this.state.id}`, data, this.config);
    }
    else {
        request = axios.post(`${USER_URL}`, data, this.config);
    }
    request.then(response => {
        if(this.props.history.length < 1) {
            this.props.history.push('/user');
        }
        else {
            this.props.history.goBack();
        }

        this.props.removeNotification(loadingNotification);
        this.props.showNotification({message: 'Usuário salvo com sucesso', title: 'Usuário Salvo', level: 'success' })
    })
  }

  handleChange = e => {
    const input = this.state[e.target.name];

    if(e.target.type == 'checkbox') {
        input.value = e.target.checked;
    }
    else {
        input.value = e.target.value;
    }

    this.setState({ [e.target.name]: input });

    this.showInputError(e.target.name);

    if(e.target.name === 'country') {
        if(input.value !== '') {
            this.fetchState(input.value);
        }
        else {this.setState({
                stateList: [], 
                state: {value:'', errors:[]},
                cityList: [],
                city: {value:'', errors:[]}
            });
        }
    }
    if(e.target.name === 'state') {
        if(input.value !== '') {
            this.fetchCity(input.value);
        }
        else {this.setState({
                cityList: [],
                city: {value:'', errors:[]}
            });
        }
    }
  }

  validateForm = () => {
    this.showInputError('name');
    this.showInputError('email');
    this.showInputError('born');
    this.showInputError('password');
    this.showInputError('confirmPassword');

    return !this.state.hasError;
  }

  showInputError = (fieldName) => {
    const field = document.getElementById(fieldName);
    let errors = [];

    switch(fieldName) {
        case 'email':
            if(field && field.value === '') {
                errors.push('required');
            } else if(!field.value.match(/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/)) {
                errors.push('notMatch');
            }
        break;
        case 'name':
            if(field && field.value === '') {
                errors.push('required');    
            } 
            else if(field && field.value.length < 3) {
                errors.push('minlength');
            }
        break;
        case 'born':
            if(field && field.value !== '' && isNaN(new Date(field.value))) {
                errors.push('notMatch');
            }
        break;
        case 'password':
            if(field && field.value.length < 8 && this.state.id < 1) {
                errors.push('minlength');
            }
            this.showInputError('confirmPassword')
        break;
        case 'confirmPassword':
        if(field && field.value !== this.state.password.value && this.state.id < 1) {
            errors.push('notMatch');    
        } 
        break;
    }

    const fieldState = this.state[fieldName];
    fieldState.errors = errors;
    
    this.setState({ [fieldName]: fieldState });
    
    const countErrors = _.transform(this.state, (result, item) => {
        if(item.errors) {
            result.total += item.errors.length;
        }
    }, {total: 0});
    
    const hasError = countErrors.total > 0;
    this.setState({ hasError });
  }

  onAvatarImageAdd = image => {
      this.setState({avatar: {value: image, errors: [] }});
  }

  onAvatarImageRemove = id => {
      this.setState({avatar: {value: {}, errors:[]}});
  }

  renderErrorsTip = (name, errors) => {
    return _.map(errors, (error) => {
        return (
            <span className="help-block sub-little-text text-danger" style={{display : _.includes(this.state[name].errors, error.name) ? '' : 'none'}}>{error.description}</span>
        );
    });  
  }

  renderInput = (name, label, input, errorsMessage) => {
    const {value, errors } = this.state[name];
    return (
        <div className="form-group row">
            <label htmlFor={name} className="col-sm-2 control-label">{label}</label>
            <div className="col-sm-10">
                <div className={errors.length > 0 ? 'form-group has-danger has-feedback' : 'form-group'}>
                    { input }
                    {_.map(errorsMessage, (error, index) => { return (
                        <span key={index} className="help-block sub-little-text text-danger" style={{display : _.includes(errors, error.name) ? '' : 'none'}}>{error.description}</span>
                    )})}
                </div>
            </div>
        </div>
    );
  }

  renderAvatar = () => {
      return this.renderInput(
        'avatar', 
        'Avatar', 
        <div className='col-sm-12 text-center'>
            <ImageFile height={200} width={200} image={ this.state.avatar.value } onImageAdd={this.onAvatarImageAdd} onImageRemove={this.onAvatarImageRemove} uploadImage={false} />
        </div>
      )
  }

  renderName = () => {
    return this.renderInput(
        'name', 
        'Nome', 
        <input type="text" name="name" id="name" placeholder="Nome" className="form-control" value={this.state.name.value} onChange={this.handleChange}/>, 
        [
            { name: 'required', description: 'O nome é obrigatório' },
            { name: 'minlength', description: 'Informe no mínimo 3 caracteres' },
        ]
    ) ;
  }

  renderEmail = () => {
    return this.renderInput(
        'email', 
        'Email', 
        <input type="email" name="email" id="email" placeholder="Email" className="form-control" value={this.state.email.value} onChange={this.handleChange}/>, 
        [
            { name: 'required', description: 'O email é obrigatório' },
            { name: 'notMatch', description: 'Email inválido' },
        ]
    ) ;
  }

  renderPhone = () => {
    return this.renderInput(
        'phone', 
        'Telefone', 
        <input type="text" name="phone" id="phone" placeholder="Telefone" className="form-control" value={this.state.phone.value} onChange={this.handleChange}/>, 
        [
            { name: 'notMatch', description: 'Telefone inválido' },
        ]
    ) ;
  }

  renderBorn = () => {
    return this.renderInput(
        'born', 
        'Data Nascimento', 
        <input type="date" name="born" id="born" placeholder="Nascimento" className="form-control" value={this.state.born.value} onChange={this.handleChange}/>, 
        [
            { name: 'notMatch', description: 'Data inválida' },
        ]
    ) ;
  }

  renderGender = () => {
    return this.renderInput(
        'gender', 
        'Gênero', 
        <select name="gender" id="gender" value={this.state.gender.value} onChange={this.handleChange} className="form-control">
            <option value="">Selecione um gênero</option>
            <option value="m">Masculino</option>
            <option value="f">Feminino</option>
        </select>
    ) 
  }

  renderPassword = () => {
    return this.renderInput(
        'password', 
        'Senha', 
        <input type="password" name="password" id="password" placeholder="Senha" className="form-control" value={this.state.password.value} onChange={this.handleChange}/>, 
        [
            { name: 'minlength', description: 'A senha deve ter no mínimo 8 caracteres' }
        ]
    ) ;
  }

  renderConfirmPassword = () => {
    return this.renderInput(
        'confirmPassword', 
        'Confirmar Senha', 
        <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Confirmar Senha" className="form-control" value={this.state.confirmPassword.value} onChange={this.handleChange}/>, 
        [
            { name: 'notMatch', description: 'As senhas não conferem' },
        ]
    ) ;
  }

  renderStreet = () => {
    return this.renderInput(
        'street', 
        'Rua', 
        <input type="text" name="street" id="street" placeholder="Rua" className="form-control" value={this.state.street.value} onChange={this.handleChange}/>
    ) ;
  }
  
  renderNumber = () => {
    return this.renderInput(
        'number', 
        'Número', 
        <input type="text" name="number" id="number" placeholder="Número" className="form-control" value={this.state.number.value} onChange={this.handleChange}/>
    ) ;
  }

  renderDistrict = () => {
    return this.renderInput(
        'district', 
        'Bairro', 
        <input type="text" name="district" id="district" placeholder="Bairro" className="form-control" value={this.state.district.value} onChange={this.handleChange}/>
    ) ;
  }

  renderCountry = () => {
    let input = '';
    if(this.state.loadingCountry) {
        input = (<div><i className="fa fa-spinner spin-animation"></i>Carregando os Paises ...</div>)
    }
    else {
        input = (
            <select name="country" id="country" value={this.state.country.value} onChange={this.handleChange} className="form-control">
                <option value="">Selecione um País</option>
                { _.map(this.state.countryList, country => { return (<option key={country.id} value={country.id}>{country.name}</option>)}) }
            </select>
        )
    }

    return this.renderInput('country', 'País', 
        input , 
        [
            {name: 'required', description: 'Selecione um País'},
        ]
    ) 
  }

  renderState = () => {
    let input = '';
    if(this.state.loadingState) {
        input = (<div><i className="fa fa-spinner spin-animation"></i>Carregando os Estados ...</div>)
    }
    else {
        input = (
        <select name="state" id="state" value={this.state.state.value} onChange={this.handleChange} className="form-control">
            <option value="">Selecione um Estado</option>
            { _.map(this.state.stateList, state => { return (<option key={state.id} value={state.id}>{state.name}</option>)}) }
        </select>)
    }

    return this.renderInput('state', 'Estado', 
            input, 
        [
            {name: 'required', description: 'Selecione um Estado'},
        ]
    ) 
  }

  renderCity = () => {
    let input = '';
    if(this.state.loadingCity) {
        input = (<div><i className="fa fa-spinner spin-animation"></i>Carregando as cidades ...</div>)
    }
    else {
        input = (
            <select name="city" id="city" value={this.state.city.value} onChange={this.handleChange} className="form-control">
                <option value="">Selecione uma cidade</option>
                { _.map(this.state.cityList, city => { return (<option key={city.id} value={city.id}>{city.name}</option>)}) }
            </select>
        )
    }

    return this.renderInput('city', 'Cidade', 
        input , 
        [
            {name: 'required', description: 'Selecione uma cidade'},
        ]
    ) 
  }

  renderPostalCode = () => {
    return this.renderInput(
        'postalCode', 
        'CEP', 
        <input type="text" name="postalCode" id="postalCode" placeholder="CEP" className="form-control" value={this.state.postalCode.value} onChange={this.handleChange}/>
    ) ;
  }
  
  renderLanguages = () => {
    return this.renderInput(
        'languages', 
        'Idiomas', 
        <input type="text" name="languages" id="languages" placeholder="Idiomas" className="form-control" value={this.state.languages.value} onChange={this.handleChange}/> 
    ) ;
  }

  renderAdmin = () => {
    return this.renderInput(
        'admin', 
        'Administrador', 
        <input type="checkbox" name="admin" id="admin" checked={this.state.admin.value} onChange={this.handleChange}/> 
    ) ;
  }

  render() {
    return (
      <div className="animated fadeIn">
        <div className="card">
          <div className="card-header">
            Salvar Lugar
          </div>
          <div className="card-block">
              {
                  this.renderAvatar()
              }
              { 
                this.renderName()
              }
              {
                this.renderEmail()
              }
              {
                this.renderPhone()
              }
              {
                this.renderPassword()
              }              
              {
                this.renderConfirmPassword()
              }              
              {
                this.renderBorn()
              }
              {
                this.renderGender()
              }
              {
                this.renderStreet()
              }
              {
                this.renderNumber()
              }
              {
                this.renderDistrict()
              }
              {
                this.renderCountry()
              }
              {
                  this.renderState()
              }
              {
                  this.renderCity()
              }
              {
                  this.renderPostalCode()
              }
              {
                  this.renderLanguages()
              }
              {
                  this.renderAdmin()
              }
              <div className="form-group row">
                  <div className="offset-sm-2">
                      <button type="submit" className="btn btn-info" disabled={this.state.hasError} onClick={this.handleSubmit}>Salvar</button>
                  </div>
              </div>
          </div>
        </div>
    </div>
    );
  }
}

export default UserEdit;
