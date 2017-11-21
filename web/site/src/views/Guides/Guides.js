import _ from 'lodash';
import debounce from 'lodash/debounce'
import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import ReactPaginate from 'react-paginate';
import axios from 'axios';

const USER_URL = 'https://guideup.com.br/api/guide';

class Guides extends Component {

constructor(props) {
  super(props);
  this.state = {
    guides: [{data: {}, current_page: 0, last_page: 0}],
    type: '',
    text: '',
    page: '1',
    loading: false
  };
}

  componentDidMount() {
    this.fetchGuides();

    this.handleFilterChange = debounce(this.handleFilterChange, 1000);
  }

  fetchGuides = (text = '', page = 1, type = '') => {
    this.setState({loading: true});

    const config = {
      headers: {
        'Authorization': "Bearer " + localStorage.getItem('token'), 
        'Accept': 'application/json' 
      }
    }
    axios.get(`${USER_URL}?page=${page}&term=${text}&type=${type}`, config)
    .then(response => {
      this.setState({guides: response.data});
      this.setState({loading: false});
    });
  }

  deleteGuide = (guide) => {
    
    if(!guide || guide.id < 1) return;

    const config = {
        headers: {
        'Authorization': "Bearer " + localStorage.getItem('token'), 
        'Accept': 'application/json' 
      }
    }

    axios.delete(`${USER_URL}/${guide.id}`, config)
    .then(response => {
      const guides = this.state.guides;
      guides.data = _.remove(guides.data, item => { return item.id !== guide.id; });
      this.setState({ guides });
    });
  }

  handlePageClick = (page) => {
    if(this.state.guides.current_page - 1 !== page.selected) {
      const selectedPage = page.selected + 1;
      this.setState({page: selectedPage});
      
      this.fetchGuides(this.state.text, selectedPage, this.state.type);
    }
  }

  handleFilterChange = (text) => {
    if(text !== '' && (text == null || text.length < 3)) {
        return;
      }
      this.setState({ text });
      this.fetchGuides(text, this.state.page, this.state.type);
  }

  handleTypeChange = (type) => {
    this.setState({ type });
      this.fetchGuides(this.state.text, this.state.page, type);
  }

  handleDetailClick = (guide) => {
    this.props.history.push(`/guide/${guide.id}`);
  }

  handleEditClick = (guide) => {
    if(guide != null && guide.id > 0)
    {
      this.props.history.push(`/guide/edit/${guide.id}`);
    }
  }

  handleDeleteClick = (guide) => {
    if(guide != null && guide.id > 0)
    {
      if(window.confirm(`Confirmar a exclusão do guia ${guide.name}?`)) {
        this.deleteGuide(guide);
      }
    }
  }

  lineText(text, lines = 1) {
    const style = {WebkitLineClamp: lines, overflow : 'hidden', textOverflow: 'ellipsis', display: '-webkit-box', WebkitBoxOrient: 'vertical'};
    return <div style={style}>{text}</div>;
  }

  addressToText(address) {
    if(!address || address.id < 1) return 'Não iformado';
    
    return this.lineText(`${address.street}, ${address.number}, ${address.district}, ${address.city_name}, ${address.state_name} - ${address.country_name}`, 2);
  }

  languageToText(languages) {
    if(!languages || languages.length < 1) return '';

    return _.map(languages, (language, key) => {
      return <span key={key}>{language.name}</span>
    });
  }

  printRows = () => {
    if(this.state.loading) {
        return <tr><td colSpan="12" style={{textAlign:"center"}}><i className="fa fa-refresh fa-spin"></i> Carregando ...</td></tr>;
    }

    if(!this.state.loading && _.isEmpty(this.state.guides.data)) {
        return <tr><td colSpan="12" style={{textAlign:"center"}}>Nenhum item encontrado</td></tr>;
    }
    return _.map(this.state.guides.data, guide => {
        return (
          <tr key={guide.id}>
            <td>
                <img style={{width: '100%'}} src={guide.avatar_url} alt="avatar thumbnail" />
            </td>
            <td>
              <div>
                  <div className="line-clamp line-clamp-2">
                    {guide.user.name}                    
                  </div>
                  <div className="small text-muted">
                      {guide.number_consil}
                  </div>
                  <div>
                    { this.languageToText(guide.languages)}
                  </div>
                </div>
            </td>
            <td>
              
              <div>
                  <div className="line-clamp line-clamp-2">
                    {guide.company}                    
                  </div>
                  <div>
                      {guide.email}
                  </div>
                  <div>
                      {guide.phone}
                  </div>
              </div>
            </td>
            <td>{this.lineText(guide.description, 3)}</td>
            <td>{ this.addressToText(guide.address) }</td>
            <td className="text-center">
               <div>{guide.score} <i className="fa fa-star" style={{color:"#ecc807"}}></i></div>
               <div>{guide.total_review} Avaliações</div>
            </td>
            <td>
              <div className="text-right" style={{width: '65px'}}>
                <button className="btn btn-link btn-sm" onClick={event => this.handleEditClick(guide)}>
                  <i className="fa fa-pencil-square-o"></i>
                </button>
                <button className="btn btn-link text-danger btn-sm" onClick={event => this.handleDeleteClick(guide)}>
                  <i className="fa fa-trash-o"></i>
                </button>
                </div>
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
                                <option value="">Todos</option>
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                                <option value="valid">Validado</option>
                                <option value="notvalid">Não validado</option>
                            </select>
                        </div>
                        <div className="col-sm-2 text-right">
                            <Link type="button" className="btn btn-primary btn-block btn-with-icon" to="/guide/new"><i className="fa fa-plus"></i>
                            &nbsp;Novo
                          </Link>
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-sm-12">
                            <table className="table table-responsive table-condensed table-hover table-outline table-bordered mb-0" style={{marginTop:'20px'}}>
                                <thead className="thead-default">
                                    <tr>
                                        <th className="text-center" style={{width: '70px'}}></th>
                                        <th style={{width: '170px'}}>Nome</th>
                                        <th>Companhia</th>
                                        <th>Descrição</th>
                                        <th>Endereço</th>
                                        <th className="text-center">Avaliações</th>
                                        <th style={{width: '65px'}} className="text-center"></th>
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
                                forcePage={this.state.guides.current_page - 1}
                                pageCount={this.state.guides.last_page}
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

export default Guides;
