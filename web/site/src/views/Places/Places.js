import _ from 'lodash';
import debounce from 'lodash/debounce'
import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import ReactPaginate from 'react-paginate';
import axios from 'axios';

class Places extends Component {

constructor(props) {
  super(props);
  this.state = {
    places: [{data: {}, current_page: 0, last_page: 0}],
    type: '',
    text: '',
    page: '1',
    loading: false
  };
}

  componentDidMount() {
    this.fetchPlaces();

    this.handleFilterChange = debounce(this.handleFilterChange, 1000);
  }

  fetchPlaces = (text = '', page = 1, type = '') => {
    this.setState({loading: true});
    axios.get(`https://guideup.com.br/api/place?page=${page}&term=${text}&type=${type}`)
    .then(response => {
      this.setState({places: response.data});
      this.setState({loading: false});
    });
  }

  deletePlace = (place) => {
    
    if(!place || place.id < 1) return;

    const config = {
        headers: {
        'Authorization': "Bearer " + localStorage.getItem('token'), 
        'Accept': 'application/json' 
      }
    }

    axios.delete(`https://guideup.com.br/api/place/${place.id}`, config)
    .then(response => {
      const places = this.state.places;
      places.data = _.remove(places.data, item => { return item.id !== place.id; });
      this.setState({ places });
    });
  }

  handlePageClick = (page) => {
    if(this.state.places.current_page - 1 !== page.selected) {
      const selectedPage = page.selected + 1;
      this.setState({page: selectedPage});
      
      this.fetchPlaces(this.state.text, selectedPage, this.state.type);
    }
  }

  handleFilterChange = (text) => {
    if(text !== '' && (text == null || text.length < 3)) {
        return;
      }
      this.setState({text: text});
      this.fetchPlaces(text, this.state.page, this.state.type);
  }

  handleTypeChange = (type) => {
    this.setState({ type });
      this.fetchPlaces(this.state.text, this.state.page, type);
  }

  handleDetailClick = (place) => {
    this.props.history.push(`/place/${place.id}`);
  }

  handleEditClick = (place) => {
    if(place != null && place.id > 0)
    {
      this.props.history.push(`/place/edit/${place.id}`);
    }
  }

  handleDeleteClick = (place) => {
    if(place != null && place.id > 0)
    {
      if(window.confirm(`Confirmar a exclusão de ${place.name}?`)) {
        this.deletePlace(place);
      }
    }
  }

  typeToText = (type) => {
    switch(type) {
      case 1:
        return <span className="badge badge-success">Lugar</span>;
      case 2:
        return <span className="badge badge-success">Cidade</span>;
      case 3:
        return <span className="badge badge-success">Estado</span>;
      case 4:
        return <span className="badge badge-success">País</span>;
      default:
      return <span className="badge badge-danger">Pendente</span>;
    }
  }

  lineText(text, lines = 1) {
    const style = {WebkitLineClamp: lines, overflow : 'hidden', textOverflow: 'ellipsis', display: '-webkit-box', WebkitBoxOrient: 'vertical'};
    return <div style={style}>{text}</div>;
  }

  printStateCountry(place) {
    if(place.state_name && place.country_name) {
      return <div><Link to={`place/edit/${place.state_id}`}>{place.state_name}</Link> - <Link to={`place/edit/${place.country_id}`}>{place.country_name}</Link></div>
    }
    else if(place.state_name) {
      return <Link to={`place/edit/${place.state_id}`}>{place.state_name}</Link>
    }
    else if(place.country_name) {
      return <Link to={`place/edit/${place.country_id}`}>{place.country_name}</Link>
    }
  }

  printRows = () => {
    if(this.state.loading) {
        return <tr><td colSpan="6" style={{textAlign:"center"}}><i className="fa fa-refresh fa-spin"></i> Carregando ...</td></tr>;
    }

    if(!this.state.loading && _.isEmpty(this.state.places.data)) {
        return <tr><td colSpan="6" style={{textAlign:"center"}}>Nenhum item encontrado</td></tr>;
    }
    return _.map(this.state.places.data, place => {
        return (
          <tr key={place.id}>
            <td>
                <img height="40" width="40" src={place.cover_thumbnail_url} alt="cover thumbnail" />
            </td>
            <td>
                <div>{place.name}</div>
                <div className="small text-muted">
                  <div>{this.lineText(place.address,2)}</div>
                  <div>{this.printStateCountry(place)}</div>
                </div>
            </td>
            <td>{this.lineText(place.description,3)}</td>
            <td className="text-center">
                { this.typeToText(place.type) }
            </td>
            <td className="text-center">
                <button className="btn btn-link" onClick={event => this.handleEditClick(place)}>
                  <i className="fa fa-pencil-square-o"></i>
                </button>
                <button className="btn btn-link text-danger" onClick={event => this.handleDeleteClick(place)}>
                  <i className="fa fa-trash-o"></i>
                </button>
            </td>
          </tr>
        );
    });
  }

  render() {
    return (
      <div className="animated fadeIn">
        <div className="row">
          <div className="col-lg-12">
            <div className="card">
                <div className="card-block">
                    <div className="row">
                        <div className="col-sm-6">
                            <input className="form-control" placeholder="Filtrar" name="filtrar" type="text" onKeyUp={event => this.handleFilterChange(event.target.value)}/>
                        </div>
                        <div className="col-sm-4">
                            <select name="type" id="type" className="form-control" value={ this.state.type } onChange={event => this.handleTypeChange(event.target.value)}>
                                <option value="">Todos Tipos</option>
                                <option value="0">Pendente</option>
                                <option value="1">Lugar</option>
                                <option value="2">Cidade</option>
                                <option value="3">Estado</option>
                                <option value="4">País</option>
                            </select>
                        </div>
                        <div className="col-sm-2 text-right">
                            <Link type="button" className="btn btn-primary btn-block btn-with-icon" to="/place/new"><i className="fa fa-plus"></i>
                            &nbsp;Novo
                          </Link>
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-sm-12">
                            <table className="table table-condensed table-hover table-outline table-bordered mb-0" style={{marginTop:'20px'}}>
                                <thead className="thead-default">
                                    <tr>
                                        <th className="text-center" style={{width: '70px'}}></th>
                                        <th style={{minWidth: '250px'}}>Lugar</th>
                                        <th>Descrição</th>
                                        <th style={{width: '90px'}} className="text-center">Tipo</th>
                                        <th style={{width: '125px'}} className="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    { this.printRows() }
                                </tbody>
                            </table>
                            <nav>
                              <ReactPaginate previousLabel={"Anterior"}
                                nextLabel={"Próximo"}
                                breakLabel={<a href="">...</a>}
                                breakClassName={"break-me"}
                                forcePage={this.state.places.current_page - 1}
                                pageCount={this.state.places.last_page}
                                marginPagesDisplayed={2}
                                pageRangeDisplayed={5}
                                onPageChange={page => this.handlePageClick(page)}
                                containerClassName={"pagination"}
                                subContainerClassName={"page-item"}
                                pageLinkClassName={"page-link"}
                                activeClassName={"active"} />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </div>
      </div>
    )
  }
}

export default Places;
