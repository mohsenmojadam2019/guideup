import _ from 'lodash';
import debounce from 'lodash/debounce'
import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import ReactPaginate from 'react-paginate';
import axios from 'axios';

const USER_URL = 'https://guideup.com.br/api/user';

class Users extends Component {

constructor(props) {
  super(props);
  this.state = {
    users: [{data: {}, current_page: 0, last_page: 0}],
    type: '',
    text: '',
    page: '1',
    loading: false
  };
}

  componentDidMount() {
    this.fetchUsers();

    this.handleFilterChange = debounce(this.handleFilterChange, 1000);
  }

  fetchUsers = (text = '', page = 1, type = '') => {
    this.setState({loading: true});

    const config = {
      headers: {
        'Authorization': "Bearer " + localStorage.getItem('token'), 
        'Accept': 'application/json' 
      }
    }
    axios.get(`${USER_URL}?page=${page}&term=${text}&type=${type}`, config)
    .then(response => {
      this.setState({users: response.data});
      this.setState({loading: false});
    });
  }

  deleteUser = (user) => {
    
    if(!user || user.id < 1) return;

    const config = {
        headers: {
        'Authorization': "Bearer " + localStorage.getItem('token'), 
        'Accept': 'application/json' 
      }
    }

    axios.delete(`${USER_URL}/${user.id}`, config)
    .then(response => {
      const users = this.state.users;
      users.data = _.remove(users.data, item => { return item.id !== user.id; });
      this.setState({ users });
    });
  }

  handlePageClick = (page) => {
    if(this.state.users.current_page - 1 !== page.selected) {
      const selectedPage = page.selected + 1;
      this.setState({page: selectedPage});
      
      this.fetchUsers(this.state.text, selectedPage, this.state.type);
    }
  }

  handleFilterChange = (text) => {
    if(text !== '' && (text == null || text.length < 3)) {
        return;
      }
      this.setState({ text });
      this.fetchUsers(text, this.state.page, this.state.type);
  }

  handleTypeChange = (type) => {
    this.setState({ type });
      this.fetchUsers(this.state.text, this.state.page, type);
  }

  handleDetailClick = (user) => {
    this.props.history.push(`/user/${user.id}`);
  }

  handleGuideClick = (user) => {
    this.props.history.push(`/guide/edit/${user.guide_id}`);
  }

  handleEditClick = (user) => {
    if(user != null && user.id > 0)
    {
      this.props.history.push(`/user/edit/${user.id}`);
    }
  }

  handleDeleteClick = (user) => {
    if(user != null && user.id > 0)
    {
      if(window.confirm(`Confirmar a exclusão do usuário ${user.name}?`)) {
        this.deleteUser(user);
      }
    }
  }

  genderToText(gender) {
    switch (gender) {
      case 'm':
        return <i className="fa fa-mars text-info" aria-hidden="true"></i>
      case 'f':
        return <i className="fa fa-venus text-danger" aria-hidden="true"></i>
      default:
        return <i className="fa fa-genderless" aria-hidden="true"></i>
    }
  }

  addressToText(address) {
    if(!address || address.id < 1) return 'Não iformado';
    const style = {width: '150px', WebkitLineClamp: 2, overflow : 'hidden', textOverflow: 'ellipsis', display: '-webkit-box', WebkitBoxOrient: 'vertical'};
    return <div style={style}>{`${address.street}, ${address.number}, ${address.district}, ${address.city_name}, ${address.state_name} - ${address.country_name}`}</div>;
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

    if(!this.state.loading && _.isEmpty(this.state.users.data)) {
        return <tr><td colSpan="12" style={{textAlign:"center"}}>Nenhum item encontrado</td></tr>;
    }
    return _.map(this.state.users.data, user => {
        return (
          <tr key={user.id}>
            <td>
                <img style={{width: '100%'}} src={user.avatar_url} alt="avatar thumbnail" />
            </td>
            <td>
              <div>
                  <div className="line-clamp line-clamp-2">
                    {user.name}
                    &nbsp;&nbsp;
                    { this.genderToText(user.gender) }
                    
                  </div>
                  <div className="small text-muted">
                      {user.email}
                  </div>
                  <div>
                    { this.languageToText(user.languages)}
                  </div>
                </div>
            </td>
            <td>{user.phone}</td>
            <td>{user.born}</td>
            <td>{ this.addressToText(user.address) }</td>
            <td><div className='text-center'>{user.is_admin ? <span className='badge badge-success'>Sim</span> : <span className='badge badge-default'>Não</span>}</div></td>
            <td><div className='text-center'>{user.state ? <span className='badge badge-success'>Ativo</span> : <span className='badge badge-danger'>Inativo</span>}</div></td>
            <td>
              <div className="text-right" style={{width: '95px'}}>
              { user.guide_id > 0  ? 
                  (<button className="btn btn-link btn-sm" onClick={event => this.handleGuideClick(user)}>
                    <i className="fa fa-id-card-o"></i>
                  </button>) : null }
                <button className="btn btn-link btn-sm" onClick={event => this.handleEditClick(user)}>
                  <i className="fa fa-pencil-square-o"></i>
                </button>
                <button className="btn btn-link text-danger btn-sm" onClick={event => this.handleDeleteClick(user)}>
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
                                <option value="guide">Guias</option>
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                                <option value="male">Homem</option>
                                <option value="female">Mulher</option>
                                <option value="nogender">Sem sexo</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div className="col-sm-2 text-right">
                            <Link type="button" className="btn btn-primary btn-block btn-with-icon" to="/user/new"><i className="fa fa-plus"></i>
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
                                        <th>Nome</th>
                                        <th>Telefone</th>
                                        <th>Nascimento</th>
                                        <th>Endereço</th>
                                        <th className="text-center">Admin</th>
                                        <th className="text-center">Situação</th>
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
                                forcePage={this.state.users.current_page - 1}
                                pageCount={this.state.users.last_page}
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

export default Users;
