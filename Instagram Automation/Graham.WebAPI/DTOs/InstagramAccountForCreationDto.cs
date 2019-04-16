using System.ComponentModel.DataAnnotations;

namespace Graham.WebAPI.DTOs
{
    public class InstagramAccountForCreationDto
    {
        [Required]
        public string Username { get; set; }

        [Required]
        public string Password { get; set; }
    }
}
