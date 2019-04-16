using AutoMapper;
using Graham.DataAccess.Model;
using Graham.WebAPI.DTOs;

namespace Graham.WebAPI.Automapper
{
    public class InstagramAccountMapper : Profile
    {
        public InstagramAccountMapper()
        {
            CreateMap<InstagramAccountForCreationDto, InstagramAccount>();
            CreateMap<InstagramAccountForUpdateDto, InstagramAccount>();  
        }
    }
}
