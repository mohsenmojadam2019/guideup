import _ from 'lodash';
import React, { Component } from 'react';
import axios from 'axios';

import {Map, InfoWindow, Marker, GoogleApiWrapper} from 'google-maps-react';

import ImageFile from '../../components/ImageFile';

const defaultState = {
    id: 0,
    cover: {value: {}, errors:[]},
    name: {value:'', errors:[]},
    description: {value:'', errors:[]},
    type: {value: 0, errors:[]},
    latitude: 0,
    longitude: 0,
    address: {value:'', errors:[]},
    country: {value:'', errors:[]},
    state: {value:'', errors:[]},
    city: {value:'', errors:[]},
    loadingCountry: false,
    countryList: [],
    loadingState: false,
    stateList: [],
    loadingCity: false,
    cityList: [],
    hasError: true,
    zoom: 14,

    galleries: []
},

PLACE_URL = 'https://guideup.com.br/api/place'

let mapService

class PlaceEdit extends Component {
    static defaultProps = {
      center: {lat: 59.95, lng: 30.33},
      zoom: 11
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
      this.fetchPlace();
      this.fetchCountry();
  }

  fetchPlace = () => {
      if(this.state.id < 1) return;

      axios.get(`${PLACE_URL}/${this.state.id}`)
        .then(response => {
            const place = response.data;
        this.setState({
            cover: {...this.state.cover, value: {id: 0, thumbnail_url: place.cover_thumbnail_url }},
            name: {...this.state.name, value: place.name },
            description: {...this.state.description, value: place.description },
            type: {...this.state.type, value: place.type },
            address: {...this.state.address, value: place.address },
            country: {...this.state.country, value: place.country_id || '' },
            state: {...this.state.state, value: place.state_id || '' },
            city: {...this.state.city, value: place.city_id || '' },
            latitude: place.latitude,
            longitude: place.longitude,
            galleries: place.galleries,
            hasError: false,
        });

        this.fetchState(place.country_id);
        this.fetchCity(place.state_id);
    })
    .catch(error => {
        console.log('error on get:', error);        
        this.props.showNotification({message: 'Erro ao exibir o lugar', title: 'Erro', level: 'error' })

        if(this.props.history.length < 1) {
            this.props.history.push('/place');
        }
        else {
            this.props.history.goBack();
        }
    })
  }

  fetchCountry = () => {
    this.setState({ loadingCountry: true })
    axios.get(`${PLACE_URL}?type=4&total=10000`)
    .then(response => {
        this.setState({countryList: response.data.data});
        this.setState({ loadingCountry: false });
    })
  }

  fetchState = (country_id) => {
      if(country_id < 1) return;

    this.setState({ loadingState: true });
    axios.get(`${PLACE_URL}?type=3&country_id=${country_id}&total=10000`).then(response => {
        this.setState({stateList: response.data.data});
        this.setState({ loadingState: false });
    })
  }
  
  fetchCity = (state_id) => {
    if(state_id < 1) return;

    this.setState({ loadingCity: true })
    axios.get(`${PLACE_URL}?type=2&state_id=${state_id}&total=10000`)
    .then(response => {
        this.setState({cityList: response.data.data});
        this.setState({ loadingCity: false });
    })
  }

  handleSubmit = e => {
    e.preventDefault();
    // TODO - Save place if all fields valid
    this.validateForm();
    
    if(this.state.hasError) {
        return;
    }
    
    const loadingNotification = this.props.showNotification({message: '<div class="text-center"><i class="fa fa-refresh fa-spin"></i> Aguarde ...</div>', title: 'Salvando', level: 'info', autoDismiss: 0, dismissible: false, position: 'tc' })

    const data = {
        name: this.state.name.value,
        description: this.state.description.value,
        type: this.state.type.value,
        latitude: this.state.latitude,
        longitude: this.state.longitude,
        address: this.state.address.value,
        country_id: this.state.country.value,
        state_id: this.state.state.value,
        city_id: this.state.city.value,
        galleries: _.map(this.state.galleries, gallery => { return gallery.id })
    }

    if(this.state.cover.value.thumbnail_url && this.state.cover.value.thumbnail_url.indexOf('/') !== -1) {
        data.cover = this.state.cover.value.thumbnail_url.split('/').pop();
    }

    const config = {
        headers: {
        'Authorization': "Bearer " + localStorage.getItem('token'), 
        'Accept': 'application/json' 
      }
    };

    let request = null;
    if(this.state.id && this.state.id > 0) {
        request = axios.put(`${PLACE_URL}/${this.state.id}`, data, config);
    }
    else {
        request = axios.post(`${PLACE_URL}`, data, config);
    }
    request.then(response => {
        if(this.props.history.length < 1) {
            this.props.history.push('/place');
        }
        else {
            this.props.history.goBack();
        }

        this.props.removeNotification(loadingNotification);
        this.props.showNotification({message: 'Lugar salvo com sucesso', title: 'Lugar Salvo', level: 'success' })
    })
  }

  handleChange = e => {
    const input = this.state[e.target.name];
    input.value = e.target.value;
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

    this.handleUpdateMapPosition();
  }

  handleMapReady = (mapProps, map) => {
    const {google} = mapProps;
    mapService = new google.maps.Geocoder;
  }

  handleMapPositionChanged = (prop, marker, ev) => {
      this.setState({ latitude: marker.position.lat(), longitude: marker.position.lng() });
  }

  handleUpdateMapPosition = () => {
    if(!this.state.address.value || this.state.address.value.trim().length < 1) return;
    if(!this.state.country || this.state.country.value.trim().length < 1) return;
    if(!this.state.state || this.state.state.value.trim().length < 1) return;
    if(!this.state.city || this.state.city.value.trim().length < 1) return;

    if(!mapService) return;

    if(window.confirm('Deseja atualizar a localização do mapa?'))
    {
        const city = _.find(this.state.cityList, (item) => item.id == this.state.city.value);
        const state = _.find(this.state.stateList, (item) => item.id == this.state.state.value);
        const country = _.find(this.state.countryList, (item) => item.id == this.state.country.value);
        const address = this.state.address.value + ", " + city.name + ", " + state.name + " - " + country.name;

        const loadingNotification = this.props.showNotification({message: '<div class="text-center"><i class="fa fa-refresh fa-spin"></i> Buscando localização ...</div>', title: 'Buscando', level: 'info', autoDismiss: 0, dismissible: false, position: 'tc' })
        
        mapService.geocode({'address': address}, (results, status) => {
            
            this.props.removeNotification(loadingNotification);

            if (status === 'OK' && results.length > 0) {
                this.setState({
                    latitude: results[0].geometry.location.lat(),
                    longitude: results[0].geometry.location.lng(),
                    zoom: 16
                });
                return;
            }
            this.props.showNotification({message: 'Erro ao atualizar a atualização do mapa', title: 'Erro localização', level: 'error' })
        });
    }

  }

  validateForm = () => {

    this.showInputError('cover');
    this.showInputError('name');
    this.showInputError('description');
    this.showInputError('type');
    this.showInputError('address');
    this.showInputError('country');
    this.showInputError('state');
    this.showInputError('city');
  }

  showInputError = (fieldName) => {
    const field = document.getElementById(fieldName);
    let errors = [];

    switch(fieldName) {
        case 'cover':
            if(!this.state.cover.value.thumbnail_url || this.state.cover.value.thumbnail_url.indexOf('http') === -1) {
                errors.push('required');
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
        case 'description':
            if(field && field.value === '') {
                errors.push('required');    
            } 
            else if(field && field.value.length < 10) {
                errors.push('minlength');
            }
        case 'address':
            if(field && field.value === '' && this.state.type.value > 0 && this.state.type.value < 2) {
                errors.push('required');    
            } 
            else if(field && field.value.length < 10) {
                errors.push('minlength');
            }
        break;
        case 'type':
            if(field && field.value === '') {
                errors.push('required');    
            } 
        break;
        case 'country':
            if(this.state.type.value > 0 && this.state.type.value < 4 && field && field.value === '') {
                errors.push('required');    
            } 
        break;
        case 'state':
            if(this.state.type.value > 0 && this.state.type.value < 3 && field && field.value === '') {
                errors.push('required');    
            } 
        break;
        case 'city':
            if(this.state.type.value > 0 && this.state.type.value < 2 && field && field.value === '') {
                errors.push('required');    
            } 
        break;
    }

    const fieldState = this.state[fieldName];
    fieldState.errors = errors;
    
    this.setState({ [fieldName]: fieldState}, () => {
        const countErrors = _.transform(this.state, (result, item) => {
            if(item.errors) {
                result.total += item.errors.length;
            }
        }, {total: 0});
        
        this.setState({ hasError: countErrors.total > 0 });
    });
  }

  onImageAdd = image => {
      this.setState({galleries: [...this.state.galleries, image]});
  }

  onImageRemove = id => {
      const galleries = _.remove(this.state.galleries, image => {
          return image.id !== id;
      })
      this.setState({ galleries });
  }
  
  onCoverImageAdd = image => {
      this.setState({cover: {value: image, errors: [] }});
  }

  onCoverImageRemove = id => {
      this.setState({cover: {value: {}, errors:['required']}});
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

  renderCover = () => {
      return this.renderInput(
        'cover', 
        'Imagem de Capa', 
        <div className='col-sm-12 text-center'>
            <ImageFile height={200} width={200} image={ this.state.cover.value } onImageAdd={this.onCoverImageAdd} onImageRemove={this.onCoverImageRemove} />
        </div>,
        [{name: 'required', description: 'A capa é obrigatória'}]
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

  renderDescription = () => {
    return this.renderInput(
        'description', 
        'Descrição', 
        <textarea name="description" id="description" placeholder="Descrição" value={this.state.description.value} onChange={this.handleChange} aria-describedby="descriptionStatus" className="form-control"></textarea>, 
        [
            {name: 'required', description: 'A descrição é obrigatória'},
            {name: 'minlength', description: 'Informe no mínimo 10 caracteres'},
        ]
    ) ;
  }

  renderType = () => {
    return this.renderInput(
        'type', 
        'Tipo', 
        <select name="type" id="type" value={this.state.type.value} onChange={this.handleChange} aria-describedby="typeStatus" className="form-control">
            <option value="0">Selecione um tipo</option>
            <option value="1">Lugar</option>
            <option value="2">Cidade</option>
            <option value="3">Estado</option>
            <option value="4">País</option>
        </select>, 
        [
            {name: 'required', description: 'Selecione um tipo'},
        ]
    ) 
  }

  renderAddress = () => {
    if(this.state.type.value > 0 && this.state.type.value < 2) {
        return this.renderInput(
            'address', 
            'Endereço', 
            <input type="text" name="address" id="address" placeholder="Endereço" value={this.state.address.value} onChange={this.handleChange} className="form-control"/>, 
            [
                {name: 'required', description: 'O endereço é obrigatório'},
                {name: 'minlength', description: 'O endereço deve ter no mínino 10 caracteres'},
            ]
        );
    }
  }

  renderCountry = () => {
    if(this.state.type.value > 0 && this.state.type.value < 4) {
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
  }

  renderState = () => {
    if(this.state.type.value > 0 && this.state.type.value < 3) {
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
  }

  renderCity = () => {
    if(this.state.type.value > 0 && this.state.type.value < 2) {
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
                  this.renderCover()
              }
              { 
                this.renderName()
              }
              {
                this.renderDescription()
              }
              {
                this.renderType()
              }
              {
                this.renderAddress()
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
              <div className="form-group row">
                  <label className="col-sm-2 form-control-label">Localização</label>
                  <div className="col-sm-10" style={{height: '500px', position: 'relative'}}>
                    <Map google={this.props.google}
                        onReady={this.handleMapReady}
                        containerStyle={{position: 'relative'}}
                        style={{width: '100%', height: '100%'}}
                        className={'map'}
                        zoom={this.state.zoom}
                        center={{ lat: this.state.latitude, lng: this.state.longitude }} >
                        <Marker
                        style={{cursor:"hand !important"}}
                        name={'SOMA'}
                        position={{ lat: this.state.latitude, lng: this.state.longitude }} 
                        draggable={true}
                        onDragend={this.handleMapPositionChanged}/>
                    </Map>
                  </div>
              </div>
              <div className="form-group row">
                  <label  className="col-sm-2 form-control-label">Galeria Fotos</label>
                  <div className="col-sm-10">
                    <ImageFile height={100} width={100} multiple={true} images={ this.state.galleries } onImageRemove={this.onImageRemove} onImageAdd={this.onImageAdd} />
                  </div>
              </div>
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

export default GoogleApiWrapper({
    apiKey: "AIzaSyBEkDj9DDDzxxVRMq8U0fHJpq2rhxZTbtc",
    libraries: ['places','visualization']
  })(PlaceEdit);
