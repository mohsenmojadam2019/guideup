import _ from 'lodash';
import React, { Component } from 'react';
import axios from 'axios';

import ImageFile from '../../components/ImageFile';

const defaultState = {
    id: 0,
    avatar: {value: {}, errors:[]},
    email: {value:'', errors:[]},
    description: {value: '', errors:[]},
    phone: {value: '', errors:[]},
    numberConsil: {value: '', errors:[]},
    company: {value: '', errors:[]},
    latitude: {value: '', errors:[]},
    longitude: {value: '', errors:[]},
    busy: {value:false, errors:[]},
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

    places: [],
    galleries: [],
    reviews:[]
}

const GUIDE_URL = 'https://guideup.com.br/api/guide';
const PLACE_URL = 'https://guideup.com.br/api/place';

class GuideEdit extends Component {

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
      this.fetchGuide();
      this.fetchCountry();
  }

  fetchGuide = () => {
      if(this.state.id < 1) return;

      axios.get(`${GUIDE_URL}/${this.state.id}`, this.config)
        .then(response => {
            const guide = response.data;
            guide.address = guide.address || {};

        this.setState({
            id: guide.id,
            avatar: {...this.state.avatar, value: {id: 0, thumbnail_url: guide.avatar_url }},
            email: {...this.state.email, value: guide.email },
            description: {...this.state.description, value: guide.description },
            phone: {...this.state.phone, value: guide.phone },
            numberConsil: {...this.state.numberConsil, value: guide.number_consil },
            company: {...this.state.company, value: guide.company },
            latitude: {...this.state.latitude, value: guide.latitude },
            longitude: {...this.state.longitude, value: guide.longitude },
            busy: {...this.state.busy, value: guide.busy },
            languages: {...this.state.languages, value: _.map(guide.languages, 'name').join(', ') || '' },
            addressId: {...this.state.addressId, value: guide.address.id || 0 },
            street: {...this.state.street, value: guide.address.street || '' },
            number: {...this.state.number, value: guide.address.number || '' },
            district: {...this.state.district, value: guide.address.district || '' },
            country: {...this.state.country, value: guide.address.country_id || '' },
            state: {...this.state.state, value: guide.address.state_id || '' },
            city: {...this.state.city, value: guide.address.city_id || '' },
            postalCode: {...this.state.postalCode, value: guide.address.postal_code || '' },
            hasError: false,

            places: guide.places || [],
            galleries: guide.galleries || [],
            reviews: guide.reviews || [],
        });

        this.fetchState(guide.address.country_id);
        this.fetchCity(guide.address.state_id);
    })
    .catch(error => {
        this.props.showNotification({message: error, title: 'Erro ao buscar o guia', level: 'error' })
        if(this.props.history.length < 1) {
            this.props.history.push('/guide');
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
    axios.get(`${PLACE_URL}?type=3&country_id=${country_id}&all=true`)
    .then(response => {
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
    // TODO - Save guide if all fields valid
    if(!this.validateForm()) {
        return;
    }

    const loadingNotification = this.props.showNotification({message: '<div class="text-center"><i class="fa fa-refresh fa-spin"></i> Aguarde ...</div>', title: 'Salvando', level: 'info', autoDismiss: 0, dismissible: false, position: 'tc' })

    let data;

    if(this.state.avatar.value) {
        this.config.headers['content-type'] = 'multipart/form-data';

        data = new FormData();
        data.append('file', this.state.avatar.value, this.state.avatar.value.name);    
        data.append('email', this.state.email.value);
        data.append('description', this.state.description.value);
        data.append('number_consil', this.state.numberConsil.value);    
        data.append('company', this.state.company.value);
        data.append('latitude', this.state.latitude.value);
        data.append('longitude', this.state.longitude.value);
        data.append('languages', this.state.languages.value);
        data.append('address[street]', this.state.street.value);
        data.append('address[number]', this.state.number.value);
        data.append('address[district]', this.state.district.value);
        data.append('address[city_id]', this.state.city.value);
        data.append('address[postal_code]', this.state.postalCode.value);
        data.append('busy', this.state.busy.value);
    }
    else {
        data = {
            email: this.state.email.value,
            description: this.state.description.value,
            phone: this.state.phone.value,
            number_consil: this.state.numberConsil.value,
            company: this.state.company.value,
            latitude: this.state.latitude.value,
            longitude: this.state.longitude.value,
            languages: this.state.languages.value,
            street: this.state.street.value,
            number: this.state.number.value,
            district: this.state.district.value,
            country_id: this.state.country.value,
            state_id: this.state.state.value,
            city_id: this.state.city.value,
            busy: this.state.busy.value,
        }
    }

    let request = null;
    if(this.state.id && this.state.id > 0) {
        data.append('_method','PUT');
        request = axios.post(`${GUIDE_URL}/${this.state.id}`, data, this.config);
    }
    else {
        request = axios.post(`${GUIDE_URL}`, data, this.config);
    }
    request.then(response => {
        if(this.props.history.length < 1) {
            this.props.history.push('/guide');
        }
        else {
            this.props.history.goBack();
        }
        
        this.props.removeNotification(loadingNotification);
        this.props.showNotification({message: 'Guia salvo com sucesso', title: 'Guia Salvo', level: 'success' })
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
    this.showInputError('avatar');
    this.showInputError('email');
    this.showInputError('description');
    this.showInputError('phone');
    this.showInputError('numberConsil');
    this.showInputError('street');
    this.showInputError('city');

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
  
  renderNumberConsil = () => {
    return this.renderInput(
        'numberConsil', 
        'Número Cadastur', 
        <input type="text" name="numberConsil" id="numberConsil" placeholder="Número Cadastur" className="form-control" value={this.state.numberConsil.value} onChange={this.handleChange}/>, 
        [
            { name: 'required', description: 'O número do conselho é obrigatório' },
            { name: 'minlength', description: 'Informe no mínimo 3 caracteres' },
        ]
    ) ;
  }

  renderDescription = () => {
    return this.renderInput(
        'description', 
        'Descrição', 
        <textarea name="description" id="description" placeholder="Descrição" className="form-control" value={this.state.description.value} onChange={this.handleChange}></textarea>, 
        [
            { name: 'required', description: 'O nome da empresa é obrigatório' },
            { name: 'minlength', description: 'Informe no mínimo 3 caracteres' },
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

  renderCompany = () => {
    return this.renderInput(
        'company', 
        'Empresa', 
        <input type="text" name="company" id="company" placeholder="Empresa" className="form-control" value={this.state.company.value} onChange={this.handleChange}/>, 
        [
            { name: 'required', description: 'O nome da empresa é obrigatório' },
            { name: 'minlength', description: 'Informe no mínimo 3 caracteres' },
        ]
    ) ;
  }
  
  renderLatitude = () => {
    return this.renderInput(
        'latitude', 
        'Latitude', 
        <input type="number" name="latitude" id="latitude" placeholder="Latitude" className="form-control" value={this.state.latitude.value} onChange={this.handleChange}/>
    ) ;
  }

  renderLongitude = () => {
    return this.renderInput(
        'longitude', 
        'Longitude', 
        <input type="number" name="longitude" id="longitude" placeholder="Longitude" className="form-control" value={this.state.longitude.value} onChange={this.handleChange}/>
    ) ;
  }
  
  renderBusy = () => {
    return this.renderInput(
        'busy', 
        'Busy', 
        <input type="checkbox" name="busy" id="busy" checked={this.state.busy.value} onChange={this.handleChange}/> 
    ) ;
  }
  
  renderLanguages = () => {
    return this.renderInput(
        'languages', 
        'Idiomas', 
        <input type="text" name="languages" id="languages" placeholder="Idiomas" className="form-control" value={this.state.languages.value} onChange={this.handleChange}/> 
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

  render() {
    return (
      <div className="animated fadeIn">
        <div className="card">
          <div className="card-header">
            {this.state.id > 0 ? "Salvar Guia" : "Novo Guia" }
          </div>
          <div className="card-block">
              {
                  this.renderAvatar()
              }
              {
                this.renderEmail()
              }
              { 
                this.renderDescription()
              }
              {
                this.renderPhone()
              }
              {
                this.renderNumberConsil()
              }              
              {
                this.renderCompany()
              }              
              {
                this.renderLatitude()
              }
              {
                this.renderLongitude()
              }
              {
                this.renderBusy()
              }
              {
                this.renderLanguages()
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

export default GuideEdit;
