import _ from 'lodash';
import debounce from 'lodash/debounce'
import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import ReactPaginate from 'react-paginate';
import axios from 'axios';
import {addLocaleData, FormattedRelative, FormattedDate, IntlProvider } from 'react-intl'
import pt from 'react-intl/locale-data/pt'

addLocaleData(pt);

const FEEDBACK_URL = 'https://guideup.com.br/api/feedback';

class Feedbacks extends Component {

constructor(props) {
  super(props);
  this.state = {
    feedbacks: [{data: {}, current_page: 0, last_page: 0}],
    type: '',
    text: '',
    page: '1',
    loading: false,
    response: '',
    showResponseField: -1
  };
}

  componentDidMount() {
    this.fetchFeedbacks();

    this.handleFilterChange = debounce(this.handleFilterChange, 1000);
  }

  fetchFeedbacks = (text = '', page = 1, type = '') => {
    this.setState({loading: true});

    const config = {
      headers: {
        'Authorization': "Bearer " + localStorage.getItem('token'), 
        'Accept': 'application/json' 
      }
    }
    axios.get(`${FEEDBACK_URL}?page=${page}&term=${text}&type=${type}`, config)
    .then(response => {
      this.setState({feedbacks: response.data});
      this.setState({loading: false});
    });
  }

  handlePageClick = (page) => {
    if(this.state.feedbacks.current_page - 1 !== page.selected) {
      const selectedPage = page.selected + 1;
      this.setState({page: selectedPage});
      
      this.fetchFeedbacks(this.state.text, selectedPage, this.state.type);
    }
  }

  handleFilterChange = (text) => {
    if(text !== '' && (text == null || text.length < 3)) {
        return;
      }
      this.setState({ text });
      this.fetchFeedbacks(text, this.state.page, this.state.type);
  }

  handleTypeChange = (type) => {
    this.setState({ type });
      this.fetchFeedbacks(this.state.text, this.state.page, type);
  }

  handleDetailClick = (feedback) => {
    this.props.history.push(`/feedback/${feedback.id}`);
  }

  handleEditClick = (feedback) => {
    if(feedback != null && feedback.id > 0)
    {
      this.props.history.push(`/feedback/edit/${feedback.id}`);
    }
  }

  handleSaveResponse = (id, response) => {
    const notification = this.props.showNotification({
      message: '<div class="text-center"><i class="fa fa-refresh fa-spin"></i> Aguarde ...</div>', 
      title: 'Salvando resposta ...', 
      level: 'info', 
      autoDismiss: 0, 
      dismissible: false, 
      position: 'tc' })

    const config = {
      headers: {
        'Authorization': "Bearer " + localStorage.getItem('token'), 
        'Accept': 'application/json' 
      }
    }
    const data = {
      response: this.state.response
    }

    axios.put(`${FEEDBACK_URL}/${id}`, data, config)
    .then(response => {
      const data = {...this.state.feedbacks.data};
      const key = _.findKey(data, ['id', id]);
      data[key]= response.data;
      this.setState({
        feedbacks: {data: data}, 
        loading: false, 
        showResponseField: -1
      });
      
      this.props.removeNotification(notification);
      this.props.showNotification({message: 'Resposta salva com sucesso', level: 'success' });
    });

  }

  lineText(text, lines = 1) {
    const style = {WebkitLineClamp: lines, overflow : 'hidden', textOverflow: 'ellipsis', display: '-webkit-box', WebkitBoxOrient: 'vertical'};
    return <div style={style}>{text}</div>;
  }

  printResponse(id, response) {
    if(this.state.showResponseField === id) {
      return (<div className="form-group">
        <label htmlFor="response">Escrever resposta:</label>
        <textarea type="text" name="response" className="form-control" placeholder="Resposta" value={this.state.response} onChange={e => this.setState({response: e.target.value})}></textarea>
        <div className="pull-right">
          <button className="btn btn-default btn-sm" onClick={e => this.setState({showResponseField: -1, response: ''})}><i className="fa fa-ban"></i> Cancelar</button>
          <button className="btn btn-primary btn-sm" onClick={e => this.handleSaveResponse(id, this.state.response)}><i className="fa fa-dot-circle-o"></i> Salvar</button>
        </div>
      </div>);
    }

    if(response == null || response == '') {
    return (<a className="btn btn-link text-danger" onClick={e => this.setState({showResponseField: id})}>Não respondido</a>);
    }
    
    return this.lineText(response, 3);
  }

  printAttachment(attachment) {
    if(attachment == null || attachment == '' || !attachment.includes('guideup.com.br')) {
      return "Sem Anexo";
    }
    return (<a className="btn btn-link text-info" href={attachment}>Anexo</a>);
  }

  printDate(date) {
    date = new Date(date.replace(' ', 'T'));
    
    var thisWeek = new Date((new Date()).valueOf() - 1000*60*60*24*2);
    thisWeek.setHours(0);
    thisWeek.setMinutes(0);
    thisWeek.setSeconds(0);
    thisWeek.setMilliseconds(0);
    
    if(date >= thisWeek) {
      return (<IntlProvider locale="pt" ><FormattedRelative value={date} /></IntlProvider>);
    }
    return (<IntlProvider locale="pt" ><FormattedDate value={date} /></IntlProvider>);
  }

  printRows = () => {
    if(this.state.loading) {
        return <tr><td colSpan="12" style={{textAlign:"center"}}><i className="fa fa-refresh fa-spin"></i> Carregando ...</td></tr>;
    }

    if(!this.state.loading && _.isEmpty(this.state.feedbacks.data)) {
        return <tr><td colSpan="12" style={{textAlign:"center"}}>Nenhum item encontrado</td></tr>;
    }
    return _.map(this.state.feedbacks.data, feedback => {
        return (
          <tr key={ feedback.id }>
            <td>
              <div>
                  <div className="line-clamp line-clamp-2">
                    { feedback.name }
                  </div>
                  <div className="small text-muted">
                      { feedback.email }
                  </div>
              </div>
            </td>
            <td>
              { this.lineText(feedback.description, 3) }
            </td>
            <td>
              { this.printResponse(feedback.id, feedback.response) }
            </td>
            <td className="text-center">
              { this.printAttachment('') }
            </td>
            <td className="text-center">
              { this.printDate(feedback.date) }
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
                        <div className="col-sm-7">
                            <input className="form-control" placeholder="Filtrar" name="filtrar" type="text" onKeyUp={event => this.handleFilterChange(event.target.value)}/>
                        </div>
                        <div className="col-sm-5">
                            <select name="type" id="type" className="form-control" value={ this.state.type } onChange={event => this.handleTypeChange(event.target.value)}>
                                <option value="">Todos</option>
                                <option value="response">Respondidos</option>
                                <option value="noresponse">Não Respondidos</option>
                            </select>
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-sm-12">
                            <table className="table table-responsive table-condensed table-hover table-outline table-bordered mb-0" style={{marginTop:'20px'}}>
                                <thead className="thead-default">
                                    <tr>
                                        <th style={{width: '170px'}}>Nome</th>
                                        <th>Comentário</th>
                                        <th style={{width: '200px'}}>Resposta</th>
                                        <th style={{width: '150px'}} className="text-center">Anexo</th>
                                        <th className="text-center">Data Envio</th>
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
                                forcePage={this.state.feedbacks.current_page - 1}
                                pageCount={this.state.feedbacks.last_page}
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

export default Feedbacks;
